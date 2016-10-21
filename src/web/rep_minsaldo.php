<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  readfile("$DOCUMENT_ROOT/head.html");
  include("$DOCUMENT_ROOT/netstorecore.php");
  session_start();
  if(!@session_is_registered('authdata')){
    header($start_url);
    }
  $authdata = $_SESSION['authdata'];
  $login = $authdata['login'];
  $passwd = $authdata['passwd'];
  $permlevel = $authdata['permlevel'];
  if($permlevel != "admin" 
      && $permlevel != "manager" 
      && $permlevel != "topmanager"){
    header($start_url);
    }
  include("top.php");
  $mysql = @mysql_connect($host, $login, $passwd); mysql_set_charset("koi8u");
  if($mysql == FALSE){
    session_timeout();
    }
  if(FALSE == mysql_select_db($db)){
    $msg = mysql_error();
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  $REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
  if($REQUEST_METHOD == "GET"){
    $y1 = mysql_escape_string($_GET["y1"]);
    $m1 = mysql_escape_string($_GET["m1"]);
    $d1 = mysql_escape_string($_GET["d1"]);
    $minlevel = 0;
    
    if($y1 == "") $y1 = date("Y");
    if($m1 == "") $m1 = date("m");
    if($d1 == "") $d1 = date("d");
		
    $order = mysql_escape_string($_GET["order"]);
    $dir = mysql_escape_string($_GET["dir"]);
    $type = mysql_escape_string($_GET["type"]);

		$dfrom = mktime(0, 0, 0, $m1, $d1, $y1);
    }
  if($REQUEST_METHOD == "POST"){
		$order = mysql_escape_string($_POST["order"]);
    $dir = mysql_escape_string($_POST["dir"]);
    $type = mysql_escape_string($_POST["type"]);
    $minlevel = mysql_escape_string($_POST["minlevel"]);
		
    }
	if($type == ""){
		$type = "ordinary";
		}
?>
    <tr>
      <td>
        <form method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
        <table>
					<tr>
            <td colspan="3">Р╕вень м╕н╕мального сальдо<input type="text" name="minlevel" value="<? echo $minlevel; ?>"></td>
          </tr>
					<tr>
            <td colspan="3">
              <input type="submit" name="ok" value="П╕дтвердити">
            </td>
          </tr>
        </table>
        <input type="hidden" name="order" value="<? echo $order;?>">
        <input type="hidden" name="dir" value="<? echo $dir;?>">
        <input type="hidden" name="type" value="<? echo $type;?>">
        </form>
      </td>
    </tr>
<?
	if($type == "other"){
		if(FALSE == check_superpasswd($mysql, $login)){
			$msg = "Нев╕рно введено пароль";
    	$msg = str_replace("\n", "<br>", $msg);
    	$msg = urlencode($msg);
    	header ("Location: show_error.php?error=".$msg);
    	mysql_close($mysql);
    	exit(1);
			}
		}
	mysql_query("BEGIN");
  // Считаем начисления на текущий период...
	$query = "SELECT
			contract.id,
			SUM(charge.value_without_vat) AS charge
			FROM contract
			LEFT JOIN service ON service.contract_id = contract.id
			LEFT JOIN charge ON service.id = charge.service_id";
  if($type == "other"){
    $query .= " WHERE service.cash = 'yes'";
    }
	else{
		$query .= " WHERE service.cash = 'no'";
		}
	$query .= " GROUP BY contract.id";
	$result = mysql_query($query);
  if($result == FALSE){
    $msg = "Error: ".mysql_error()." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
		mysql_query("ROLLBACK");
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($result);
	$charge = array();
	for($i = 0; $i < $n; $i++){
		$contract_id = mysql_result($result, $i, "contract.id");
		$charge[$contract_id] = mysql_result($result, $i, "charge");
		}
	// Считаем платежи на текущий период...
	$query = "SELECT
			contract.id,
			SUM(payment.value_without_vat) AS payment
			FROM contract
			LEFT JOIN payment ON payment.contract_id = contract.id";
  if($type == "other"){
    $query .= " WHERE payment.cash = 'yes'";
    }
	else{
		$query .= " WHERE payment.cash = 'no'";
		}
	$query .= " GROUP BY contract.id";
  $result = mysql_query($query);
  if($result == FALSE){
    $msg = "Error: ".mysql_error()." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
		mysql_close($mysql);
    exit(1);
    }
	//echo $query;
	//exit(1);
  $n = mysql_num_rows($result);
	$payment = array();
	for($i = 0; $i < $n; $i++){
		$contract_id = mysql_result($result, $i, "contract.id");
		$payment[$contract_id] = mysql_result($result, $i, "payment");
		}
	$cash_cond = ($type == "other")? "yes" : "no";
	$query = "SELECT
			contract.id,
			contract.c_type,
			contract.c_number,
			client.id,
			client.description,
			client.phone,
			client.manager_id,
			cluster.id,
			cluster.description,
			SUM(IF(service.cash = '$cash_cond', 1, 0)) AS serv_cash
			FROM contract
			LEFT JOIN service ON service.contract_id = contract.id
			LEFT JOIN client ON client.id = contract.client_id
			LEFT JOIN client_cluster ON client_cluster.client_id = client.id
			LEFT JOIN cluster ON cluster.id = client_cluster.cluster_id
			WHERE (client.inactivation_time IS NULL OR client.inactivation_time > NOW())
			GROUP BY contract.id
			HAVING serv_cash > 0";
	if($permlevel == "manager"){
    $query .= " AND client.manager_id = '$login'";
    }
  if($order == ""){
    $order = "cluster.description, client.description";
    }
  $query .= " ORDER BY $order $dir";
  $dir = ($dir == "ASC") ? "DESC" : "ASC";
  $result = mysql_query($query);
  if($result == FALSE){
    $msg = "Error: ".mysql_error()." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($result);

?>
    <tr>
      <td>
        <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver" width="100%">
        <caption>Сальдо кл╕╓нт╕в на <b><? echo strftime("%d %B %Y р.", time());?></b></caption>
        <tr bgcolor="#EDEDED">
          <th># п/п</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.description&amp;dir=".urlencode($dir).$url_ts?>">Назва кл╕╓нта</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=cluster.description&amp;dir=".urlencode($dir).$url_ts?>">Б╕знес-центр</a></th>
          <th>Номер Договора</th>
          <th>Сальдо</th>
        </tr>
<?
	$sum_saldo = 0;
	$j = 0;
	for($i = 0; $i < $n; $i++){
    $client_id = mysql_result($result, $i, "client.id");
    $cluster_id = mysql_result($result, $i, "cluster.id");
    $contract_id = mysql_result($result, $i, "contract.id");
    $description = mysql_result($result, $i, "client.description");
    $phone = mysql_result($result, $i, "client.phone");
    $bc_description = mysql_result($result, $i, "cluster.description");
		if($bc_description == ""){
			$bc_description = "Вид╕лена л╕н╕я";
			}
    $c_type = mysql_result($result, $i, "contract.c_type");
    $c_number = mysql_result($result, $i, "contract.c_number");
		$saldo = round2($payment[$contract_id] - $charge[$contract_id]);
		if(!($saldo < $minlevel)){
			continue;
			}
		$sum_saldo = round2($sum_saldo + $saldo);
?>
          <tr bgcolor="white">
            <td align="right"><? echo ++$j;?></td>
            <td><a href="show_client.php?client_id=<? echo $client_id;?>"><? echo "$description"."<b>(".$phone.")</b>";?></a></td>
            <td><a href="show_ll.php?cluster_id=<? echo $cluster_id;?>"><? echo "$bc_description";?></a></td>
            <td><a href="show_contract.php?client_id=<? echo $client_id;?>"><? echo $c_type."-".$c_number;?></a></td>
            <td align="right" nowrap><b><? echo number_format($saldo, 2, ".", " ");?></b></td>
          </tr>
<?
    }
?>
          <tr bgcolor="white">
            <th align="right" colspan="4">Всього</th>
            <td align="right" nowrap><b><? echo number_format($sum_saldo, 2, ".", " ");?></b></td>
          </tr>
        </table>
      </td>
    </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
