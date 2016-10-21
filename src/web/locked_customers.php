<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  include("$DOCUMENT_ROOT/netstorecore.php");
  readfile("$DOCUMENT_ROOT/head.html");
	session_start();
  if(!@session_is_registered('authdata')){
    header($start_url);
    }
  if($_SESSION['authdata']['permlevel'] != 'admin' 
      && $_SESSION['authdata']['permlevel'] != 'topmanager'
			&& $_SESSION['authdata']['permlevel'] != 'support'
			&& $_SESSION['authdata']['permlevel'] != 'juniorsupport'
			){
    header($start_url);
    }
 
  $authdata = $_SESSION['authdata'];
  $login = $authdata['login'];
  $passwd = $authdata['passwd'];
  $permlevel = $authdata['permlevel'];
  $mysql = @mysql_connect($host, $login, $passwd); mysql_set_charset("koi8u");
  if($mysql == FALSE){
    session_timeout();
		}
  if(FALSE == mysql_select_db($db)){
    $msg = "Error: ".mysql_error()." while connecting to database ".$db;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
	include("top.php");
	// x1 - date of the first day of the current month
	// x2 - date of the first day of the next month
  $y1 = date("Y");
  $m1 = date("m");
  $d1 = 1;
	
	$y2 = ($m1 == 12) ? $y1 + 1: $y1;
	$m2 = ($m1 == 12 ) ? 1 : $m1 + 1;
  $d2 = 1;
	// Получить начисления
	$query = "SELECT
			client.id,
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
			FROM client
			LEFT JOIN contract ON client.id = contract.client_id
			LEFT JOIN service ON service.contract_id = contract.id
			LEFT JOIN charge ON service.id = charge.service_id
			LEFT JOIN tariff ON tariff.id = service.tariff_id
			WHERE client.blocked = 'y'
			AND (client.inactivation_time IS NULL OR client.inactivation_time > NOW())";
  $query .= " AND service.cash = '". (($type == "other") ? "yes" : "no") . "'";
	$query .= " GROUP BY client.id";
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
		$client_id = mysql_result($result, $i, "client.id");
		$monthly_charge1[$client_id] = mysql_result($result, $i, "monthly_charge1");
		$not_monthly_charge1[$client_id] = mysql_result($result, $i, "not_monthly_charge1");
		$monthly_charge2[$client_id] = mysql_result($result, $i, "monthly_charge2");
		$not_monthly_charge2[$client_id] = mysql_result($result, $i, "not_monthly_charge2");
		}
	//	
	// Получить платежи
	$query = "SELECT
			client.id,
			contract.id,
			SUM(IF(payment.timestamp < '$y1-$m1-$d1', payment.value_without_vat, 0)) AS payment1,
			SUM(IF(payment.timestamp < '$y2-$m2-$d2', payment.value_without_vat, 0)) AS payment2
			FROM client
			LEFT JOIN contract ON contract.client_id = client.id
			LEFT JOIN payment ON payment.contract_id = contract.id";
	$query .= " WHERE payment.cash = '". (($type == "other") ? "yes" : "no") . "'";
	$query .= " GROUP BY client.id";
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
	$payment = array();
	$payment1 = array();
	$payment2 = array();
	for($i = 0; $i < $n; $i++){
		$client_id = mysql_result($result, $i, "client.id");
		$payment1[$client_id] = mysql_result($result, $i, "payment1");
		$payment2[$client_id] = mysql_result($result, $i, "payment2");
		$payment[$client_id] = round2($payment2[$client_id] - $payment1[$client_id]);
		}
	
	//
	$query = "SELECT
	client.id,
	client.login,
	client.description,
	client.activation_time,
	client.blocking_time 
	FROM client
	WHERE client.blocked = 'y'
	AND (client.inactivation_time IS NULL OR client.inactivation_time > NOW())";
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
?>
  <tr>
    <td valign="top" width="20%"><? include("left_rp.php");?></td>
    <td width="80%" valign="top">
			<form name="register_by_period" method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
			<table width="100%" cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
			<caption>Список тимчасово закритих кл╕╓нт╕в</caption>
				<tr>
					<td>Лог╕н</td>
					<td>Назва</td>
					<td>Дата початку роботи</td>
					<td>Дата блокування</td>
					<td nowrap>Сальдо<br>(на <b><? echo "$y2-$m2-$d2"; ?></b>)</td>
				</tr>
<?
	$color1 ="#FFF4AB";
	$color2 = "white";
	$color = $color1;
	for($i = 0; $i < mysql_num_rows($result); $i++){
		$color = ($color == $color1) ? $color2: $color1;
		$client_id = mysql_result($result, $i, "client.id");	
		$saldo2 = round2($payment2[$client_id] - ($monthly_charge2[$client_id] + $not_monthly_charge2[$client_id]));
?>
				<tr bgcolor="<? echo $color; ?>">
					<td><a href="show_client.php?client_id=<? echo $client_id; ?>"><? echo mysql_result($result, $i, "client.login")?></a></td>
					<td><a href="show_client.php?client_id=<? echo $client_id; ?>"><? echo mysql_result($result, $i, "client.description")?></a></td>
					<td><? echo mysql_result($result, $i, "client.activation_time")?></td>
					<td><? echo mysql_result($result, $i, "client.blocking_time")?></td>
					<td align="right" nowrap><a href="show_account.php?client_id=<? echo $client_id; ?>"><? echo number_format($saldo2, 2, ".", " "); ?></a></td>
				</tr>
<?
		}
?>
			</table>
			</form>
		</td>
	</tr>
<?
	readfile("$DOCUMENT_ROOT/bottom.html");
?>
