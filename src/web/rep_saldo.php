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
    $y1 = mysql_escape_string($_POST["y1"]);
    $m1 = mysql_escape_string($_POST["m1"]);
    $d1 = mysql_escape_string($_POST["d1"]);
    $y2 = mysql_escape_string($_POST["y2"]);
    $m2 = mysql_escape_string($_POST["m2"]);
    $d2 = mysql_escape_string($_POST["d2"]);
    
		$order = mysql_escape_string($_POST["order"]);
    $dir = mysql_escape_string($_POST["dir"]);
    $type = mysql_escape_string($_POST["type"]);
    $only_debtors = mysql_escape_string($_POST["only_debtors"]);
		
		$dfrom = mktime(0, 0, 0, $m1, $d1, $y1);
		$dto = mktime(0, 0, 0, $m2, $d2, $y2);
    }
	if($type == ""){
		$type = "ordinary";
		}
	$url_ts = "&amp;y1=$y1&amp;m1=$m1&amp;d1=$d1&amp;y2=$y2&amp;m2=$m2&amp;d2=$d2;&amp;type=$type";
?>
    <tr>
      <td>
        <form method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
        <table border="1">
          <tr>
            <td>
              <select name="d1">
<?
  for($j = 1; $j <= 31; $j++){
?>
                <option <? if($j == $d1){ echo "selected"; }?>><? echo $j;?></option>
<?
    }
?>
              </select>
            </td>
            <td>
              <select name="m1">
<?
  for($j = 1; $j <= 12; $j++){
?>
                <option <? if($j == $m1){ echo "selected"; }?> value="<? echo $j;?>"><? echo strftime("%B", mktime(0, 0, 0, $j, 1, 2000));?></option>
<?
    }
?>
              </select>
            </td>
            <td>
              <select name="y1">
<?
  $y = date("Y");
  for($j = $y - 3; $j <= $y + 5; $j++){
?>
                <option <? if($j == $y1){ echo "selected"; }?>><? echo $j;?></option>
<?
    }
?>
              </select>
            </td>
						<td>
							<table>
								<tr>
									<td><input type="checkbox" name="only_debtors" <? if($only_debtors == "on") echo "checked";?>></td>
									<td>Т╕льки боржники</td>
								</tr>
							</table>
						</td>
          </tr>
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
  for($j = $y - 3; $j <= $y + 5; $j++){
?>
                <option <? if($j == $y2){ echo "selected"; }?>><? echo $j;?></option>
<?
    }
?>
              </select>
            </td>
            <td>
              <input type="submit" name="ok" value="Зм╕нити пер╕од">
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
  // Считаем начисления на конец и начало периода...
	$query = "SELECT
			contract.id,
			SUM(IF(tariff.monthlypayment = 'yes' AND charge.timestamp < '$y1-$m1-$d1',
					charge.value_without_vat, 0)) AS monthly_charge1,
			SUM(IF(tariff.monthlypayment = 'no'
          AND charge.timestamp < DATE_SUB('$y1-$m1-$d1', INTERVAL 1 MONTH),
          charge.value_without_vat, 0)) AS not_monthly_charge1,
			SUM(IF(tariff.monthlypayment = 'yes'
          AND charge.timestamp < '$y2-$m2-$d2',
          charge.value_without_vat, 0)) AS monthly_charge2,
			SUM(IF(tariff.monthlypayment = 'no'
          AND charge.timestamp < '$y1-$m1-$d1',
          charge.value_without_vat, 0)) AS not_monthly_charge2
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
			SUM(IF(payment.timestamp < '$y1-$m1-$d1', payment.value_without_vat, 0)) AS payment1,
			SUM(IF(payment.timestamp < '$y2-$m2-$d2', payment.value_without_vat, 0)) AS payment2
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
		$payment[$contract_id] = round2($payment2[$contract_id] - $payment1[$contract_id]);
		}
	$cash_cond = ($type == "other")? "yes" : "no";
	$query = "SELECT
			contract.id,
			contract.c_type,
			contract.c_number,
			client.id,
			client.description,
			client.manager_id,
			client.phone,
			cluster.id,
			cluster.description,
			SUM(IF(service.cash = '$cash_cond', 1, 0)) AS serv_cash
			FROM contract
			LEFT JOIN service ON service.contract_id = contract.id
			LEFT JOIN client ON client.id = contract.client_id
			LEFT JOIN client_cluster ON client_cluster.client_id = client.id
			LEFT JOIN cluster ON cluster.id = client_cluster.cluster_id
			WHERE (client.activation_time <= '$y1-$m1-$d1') 
			AND (client.inactivation_time IS NULL OR client.inactivation_time > '$y2-$m2-$d2')
			AND (contract.expire_time = '0000-00-00' OR contract.expire_time > '$y1-$m1-$d1')
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
        <caption>Сальдо кл╕╓нт╕в на пер╕од з <b><? echo strftime("%d %B %Y р.", $dfrom);?></b> по <b><? echo strftime("%d %B %Y р.", $dto);?></b><br><font color="#B426D0"><strong>УВАГА! Вс╕ суми без ПДВ</strong></font></caption>
        <tr bgcolor="#EDEDED">
          <th># п/п</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.description&amp;dir=".urlencode($dir).$url_ts?>">Назва кл╕╓нта</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=cluster.description&amp;dir=".urlencode($dir).$url_ts?>">Б╕знес-центр</a></th>
          <th>Номер Договора</th>
          <th>Сальдо на початок пер╕ода</th>
          <th>Нараховано</th>
          <th>Сума оплат</th>
          <th>Сальдо на к╕нець пер╕ода</th>
        </tr>
<?
  $sum_bill = 0;
	$sum_payment = 0;
	$npp = 1;
	$sum_saldo2 = 0;
	$sum_saldo1 = 0;
	$sum_saldo_n1 = 0;
	$sum_saldo_p1 = 0;
	$sum_saldo_n2 = 0;
	$sum_saldo_p2 = 0;
	$color1 ="#FFF4AB";
	$color2 = "white";
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
		$saldo1 = round2($payment1[$contract_id] - ($monthly_charge1[$contract_id] + $not_monthly_charge1[$contract_id]));
		$saldo2 = round2($payment2[$contract_id] - ($monthly_charge2[$contract_id] + $not_monthly_charge2[$contract_id]));
		if($only_debtors == "on" && $saldo2 >= 0){
			continue;
			}
		$charged = round2(($monthly_charge2[$contract_id] + $not_monthly_charge2[$contract_id]) 
				- ($monthly_charge1[$contract_id] + $not_monthly_charge1[$contract_id]));
		$sum_bill = round2($sum_bill + $charged);
		$sum_payment = round2($sum_payment + $payment[$contract_id]);
		$sum_saldo1 = round2($sum_saldo1 + $saldo1);
		$sum_saldo_n1 = round2($sum_saldo_n1 + ($saldo1 < 0 ? $saldo1 : 0));
		$sum_saldo_p1 = round2($sum_saldo_p1 + ($saldo1 > 0 ? $saldo1 : 0));
		$sum_saldo2 = round2($sum_saldo2 + $saldo2);
		$sum_saldo_n2 = round2($sum_saldo_n2 + ($saldo2 < 0 ? $saldo2 : 0));
		$sum_saldo_p2 = round2($sum_saldo_p2 + ($saldo2 > 0 ? $saldo2 : 0));
		$color = ($color == $color1) ? $color2: $color1;
?>
          <tr bgcolor="<? echo $color; ?>">
            <td align="right"><? echo $npp;?></td>
            <td><a href="show_client.php?client_id=<? echo $client_id;?>"><? echo "$description"."<b>(".$phone.")</b>";?></a></td>
            <td><a href="show_ll.php?cluster_id=<? echo $cluster_id;?>"><? echo "$bc_description";?></a></td>
            <td><a href="show_contract.php?client_id=<? echo $client_id;?>"><? echo $c_type."-".$c_number;?></a></td>
            <td align="right" nowrap><? echo number_format($saldo1, 2, ".", " ");?></td>
            <td align="right" nowrap><? echo number_format($charged, 2, ".", " ");?></td>
            <td align="right" nowrap><? echo number_format($payment[$contract_id], 2, ".", " ");?></td>
            <td align="right" nowrap><? echo number_format($saldo2, 2, ".", " ");?></td>
          </tr>
<?
		$npp++;
    }
?>
          <tr bgcolor="white">
            <td align="right" colspan="4"><small>сума додатн╕х сальдо</small></td>
            <td align="right" nowrap><? echo number_format($sum_saldo_p1, 2, ".", " ");?></td>
            <td colspan="2">&nbsp;</td>
            <td align="right" nowrap><? echo number_format($sum_saldo_p2, 2, ".", " ");?></td>
          </tr>
          <tr bgcolor="white">
            <td align="right" colspan="4"><small>сума в╕д'╓мних сальдо</small></td>
            <td align="right" nowrap><? echo number_format($sum_saldo_n1, 2, ".", " ");?></td>
            <td colspan="2">&nbsp;</td>
            <td align="right" nowrap><? echo number_format($sum_saldo_n2, 2, ".", " ");?></td>
          </tr>
          <tr bgcolor="white">
            <th align="right" colspan="4">Всього</th>
            <td align="right" nowrap><b><? echo number_format($sum_saldo1, 2, ".", " ");?></b></td>
            <td align="right" nowrap><b><? echo number_format($sum_bill, 2, ".", " ");?></b></td>
            <td align="right" nowrap><b><? echo number_format($sum_payment, 2, ".", " ");?></b></td>
            <td align="right" nowrap><b><? echo number_format($sum_saldo2, 2, ".", " ");?></b></td>
          </tr>
        </table>
      </td>
    </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
