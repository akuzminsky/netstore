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
    $y2 = mysql_escape_string($_GET["y2"]);
    $m2 = mysql_escape_string($_GET["m2"]);
    $d2 = mysql_escape_string($_GET["d2"]);
    
    if($y1 == "") $y1 = date("Y");
    if($m1 == "") $m1 = date("m");
    if($d1 == "") $d1 = 1;
		
    if($y2 == ""){
			$y2 = ($m1 == 12) ? $y1 + 1: $y1;
			}
    if($m2 == ""){
			$m2 = ($m1 == 12 ) ? 1 : $m1 + 1;
			}
    if($d2 == "") $d2 = 1;
    $order = mysql_escape_string($_GET["order"]);
    $dir = mysql_escape_string($_GET["dir"]);
    $type = mysql_escape_string($_GET["type"]);

		$dfrom = mktime(0, 0, 0, $m1, $d1, $y1);
		$dto = mktime(0, 0, 0, $m2, $d2, $y2);
    }
  if($REQUEST_METHOD == "POST"){
    $y2 = mysql_escape_string($_POST["y2"]);
    $m2 = mysql_escape_string($_POST["m2"]);
    $d2 = mysql_escape_string($_POST["d2"]);
   	
		$num = $_POST["num"];
		mysql_query("begin");
		for($i = 0; $i < $num; $i++){
			if($_POST["payment".$i] != ""){
				$contract_id = $_POST["contract_id".$i];
				$payment = $_POST["payment".$i];
				$query = "insert into payment( contract_id,  timestamp , value, operator, notice)
			      values('$contract_id', '$y2-$m2-$d2', '$payment', '$login', 'корегуючий плат╕ж')";
				
				if(mysql_query($query) == FALSE){
					$msg = "Error: ".mysql_error()." while executing:\n".$query;
					$msg = str_replace("\n", "<br>", $msg);
					$msg = urlencode($msg);
					header ("Location: show_error.php?error=".$msg);
					mysql_query("ROLLBACK");
					mysql_close($mysql);
					exit(0);
					}
				}
			}
		mysql_query("commit");
		header("Location: set_saldo.php");
		exit(0);
		$dfrom = mktime(0, 0, 0, $m1, $d1, $y1);
		$dto = mktime(0, 0, 0, $m2, $d2, $y2);
    }
	if($type == ""){
		$type = "ordinary";
		}
	$url_ts = "&amp;y1=$y1&amp;m1=$m1&amp;d1=$d1&amp;y2=$y2&amp;m2=$m2&amp;d2=$d2;&amp;type=$type";
?>
    <form method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
    <tr>
      <td>
        <table>
          <tr>
            <td>
              <select name="d2">
<?
  for($j = 1; $j <= 31; $j++){
?>
                <option <? if($j == $d2){ echo "selected"; }?>><? echo $j;?></option>
<?
    }
?>
              </select>
            </td>
            <td>
              <select name="m2">
<?
  for($j = 1; $j <= 12; $j++){
?>
                <option <? if($j == $m2){ echo "selected"; }?> value="<? echo $j;?>"><? echo strftime("%B", mktime(0, 0, 0, $j, 1, 2000));?></option>
<?
    }
?>
              </select>
            </td>
            <td>
              <select name="y2">
<?
  $y = date("Y");
  for($j = $y - 3; $j <= $y + 5; $j++){
?>
                <option <? if($j == $y2){ echo "selected"; }?>><? echo $j;?></option>
<?
    }
?>
              </select>
            </td>
            <td>
              <input type="submit" name="ok" value="Зберегти">
            </td>
          </tr>
        </table>
        <input type="hidden" name="order" value="<? echo $order;?>">
        <input type="hidden" name="dir" value="<? echo $dir;?>">
        <input type="hidden" name="type" value="<? echo $type;?>">
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
  // Считаем начисления на конец и начало периода...
	$query = "SELECT
			contract.id,
			SUM(IF(tariff.monthlypayment = 'yes' AND charge.timestamp < '$y1-$m1-$d1',
					charge.value, 0)) AS monthly_charge1,
			SUM(IF(tariff.monthlypayment = 'no'
          AND charge.timestamp < DATE_SUB('$y1-$m1-$d1', INTERVAL 1 MONTH),
          charge.value, 0)) AS not_monthly_charge1,
			SUM(IF(tariff.monthlypayment = 'yes'
          AND charge.timestamp < '$y2-$m2-$d2',
          charge.value, 0)) AS monthly_charge2,
			SUM(IF(tariff.monthlypayment = 'no'
          AND charge.timestamp < '$y1-$m1-$d1',
          charge.value, 0)) AS not_monthly_charge2
			FROM contract
			LEFT JOIN service ON service.contract_id = contract.id
			LEFT JOIN charge ON service.id = charge.service_id
			LEFT JOIN tariff ON tariff.id = service.tariff_id";
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
	$monthly_charge1 = array();
	$not_monthly_charge1 = array();
	$monthly_charge2 = array();
	$not_monthly_charge2 = array();
	for($i = 0; $i < $n; $i++){
		$contract_id = mysql_result($result, $i, "contract.id");
		$monthly_charge1[$contract_id] = mysql_result($result, $i, "monthly_charge1");
		$not_monthly_charge1[$contract_id] = mysql_result($result, $i, "not_monthly_charge1");
		$monthly_charge2[$contract_id] = mysql_result($result, $i, "monthly_charge2");
		$not_monthly_charge2[$contract_id] = mysql_result($result, $i, "not_monthly_charge2");
		}
	// Считаем платежи за период
	$query = "SELECT
			contract.id,
			SUM(IF(payment.timestamp < '$y1-$m1-$d1', payment.value, 0)) AS payment1,
			SUM(IF(payment.timestamp < '$y2-$m2-$d2', payment.value, 0)) AS payment2
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
	$payment1 = array();
	$payment2 = array();
	for($i = 0; $i < $n; $i++){
		$contract_id = mysql_result($result, $i, "contract.id");
		$payment1[$contract_id] = mysql_result($result, $i, "payment1");
		$payment2[$contract_id] = mysql_result($result, $i, "payment2");
		$payment[$contract_id] = round($payment2[$contract_id] - $payment1[$contract_id], 2);
		}
	// И теперь считаем суммы выставленных счетов за период
	$cash_cond = ($type == "other")? "yes" : "no";
	$query = "SELECT
			contract.id,
			contract.c_type,
			contract.c_number,
			client.id,
			client.description,
			client.phone,
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
        <caption>Сальдо кл╕╓нт╕в на пер╕од з <b><? echo strftime("%d %B %Y р.", $dfrom);?></b> по <b><? echo strftime("%d %B %Y р.", $dto);?></b></caption>
        <tr bgcolor="#EDEDED">
          <th># п/п</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.description&amp;dir=".urlencode($dir).$url_ts?>">Назва кл╕╓нта</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=cluster.description&amp;dir=".urlencode($dir).$url_ts?>">Б╕знес-центр</a></th>
          <th>Номер Договора</th>
          <th>Сальдо на к╕нець пер╕ода</th>
          <th>Корегуючий плат╕ж</th>
        </tr>
<?
  $sum_bill = 0;
	$sum_payment = 0;
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
		$saldo1 = round($payment1[$contract_id] - ($monthly_charge1[$contract_id] + $not_monthly_charge1[$contract_id]), 2);
		$saldo2 = round($payment2[$contract_id] - ($monthly_charge2[$contract_id] + $not_monthly_charge2[$contract_id]), 2);
		$charged = round(($monthly_charge2[$contract_id] + $not_monthly_charge2[$contract_id]) 
				- ($monthly_charge1[$contract_id] + $not_monthly_charge1[$contract_id]), 2);
		$sum_bill = round($sum_bill + $charged, 2);
		$sum_payment = round($sum_payment + $payment[$contract_id], 2);
?>
          <tr bgcolor="white">
            <td align="right"><? echo $i + 1;?><input type="hidden" name="contract_id<?echo $i?>" value="<? echo $contract_id; ?>"></td>
            <td><a href="show_client.php?client_id=<? echo $client_id;?>"><? echo "$description"."<b>(".$phone.")</b>";?></a></td>
            <td><a href="show_ll.php?cluster_id=<? echo $cluster_id;?>"><? echo "$bc_description";?></a></td>
            <td><a href="show_contract.php?client_id=<? echo $client_id;?>"><? echo $c_type."-".$c_number;?></a></td>
            <td align="right" nowrap><? echo number_format($saldo2, 2, ".", " ");?></td>
            <td><input type="text" name="payment<? echo $i; ?>"></td>
          </tr>
<?
    }
?>
				<input type="hidden" name="num" value="<? echo $n; ?>">
        </table>
      </td>
    </tr>
		</form>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
