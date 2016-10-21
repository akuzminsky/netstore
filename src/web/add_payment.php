<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  readfile("$DOCUMENT_ROOT/head.html");
  include("$DOCUMENT_ROOT/netstorecore.php");
  session_start();
  if(!@session_is_registered('authdata')){
    header($start_url);
    }
  if($_SESSION['authdata']['permlevel'] != 'admin' 
      && $_SESSION['authdata']['permlevel'] != 'manager'
      && $_SESSION['authdata']['permlevel'] != 'topmanager'){
    header($start_url);
    }
 
  $authdata = $_SESSION['authdata'];
  include("top.php");
  $login = $authdata['login'];
  $passwd = $authdata['passwd'];
  $permlevel = $authdata['permlevel'];
  $mysql = @mysql_connect($host, $login, $passwd); mysql_set_charset("koi8u");
  if($mysql == FALSE){
    $msg = mysql_error();
    header ("Location: show_error.php?error=".$msg);
    exit(1);
    }
  if(FALSE == mysql_select_db($db)){
    $msg = mysql_error();
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  $client_id = mysql_escape_string($_GET["client_id"]);
  $query = "select client.id,
    client.description,
    client.manager_id,
    client.person,
    client.phone,
    client.email,
    client.port,
		client.tac_status,
    client.notes
    from client
    where client.id = '$client_id'";
  $result = mysql_query($query);
  if($result == FALSE){
    $msg = "Error: ".mysql_error()." while executing ".$query;
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  $responsible_manager = mysql_result($result, 0, "client.manager_id");
  $tac_status = mysql_result($result, 0, "client.tac_status");
  if($permlevel == 'manager'){
    $readonlystatus = "readonly";
    if($login != $responsible_manager){
      header($start_url);
      }
    }
  if($responsible_manager == ''){
    $responsible_manager = 'Нет';
    }
  if(!empty($_POST["create"])){
		mysql_query("BEGIN");
		$y1 = mysql_escape_string($_POST["year"]);
		$m1 = mysql_escape_string($_POST["month"]);
		$d1 = mysql_escape_string($_POST["day"]);
		$pay_date = "$y1-$m1-$d1";
		$notice = mysql_escape_string($_POST["notice"]);
		if($_POST["bill_id"] == 0){
		  $contract_id = mysql_escape_string($_POST["contract_id"]);
		  $value_without_vat = mysql_escape_string($_POST["sum"]);
			$notice = mysql_escape_string($_POST["notice"]);
			$cash = (mysql_escape_string($_POST["cash"]) == "on") ? "yes": "no";
			}
		else{ // payment according to invoice
			$bill_id = mysql_escape_string($_POST["bill_id"]);
			$query = "SELECT bill.contract_id,
					bill_value.to_order_without_vat 
					FROM bill
					LEFT JOIN bill_value ON bill_value.bill_id = bill.id
					WHERE bill.id  = '$bill_id'";
			$result = mysql_query($query);
			if($result == FALSE){
			  $msg = "Error: ".mysql_error()." while executing ".$query;
			  header ("Location: show_error.php?error=".$msg);
			  mysql_query("ROLLBACK");
			  mysql_close($mysql);
			  exit(1);
			  }
			$n = mysql_num_rows($result);
			if($n == 0){
				$msg = "Не знайдено жодного рахунку з номером $bill_id";
				header ("Location: show_error.php?error=".$msg);
				mysql_query("ROLLBACK");
				mysql_close($mysql);
				exit(1);
				}
			else{
				$value_without_vat = mysql_result($result, 0, "bill_value.to_order_without_vat");
				$contract_id = mysql_result($result, 0, "bill.contract_id");
				$cash = "no";
				$nl = ($notice == "") ? "" : "\n";
				$notice .= $nl."Оплата рахунку #$bill_id";
				$query = "UPDATE bill SET status = 'paid' WHERE id = '$bill_id'";
				if(FALSE == mysql_query($query)){
				  $msg = "Error: ".mysql_error()." while executing ".$query;
				  header ("Location: show_error.php?error=".$msg);
				  mysql_query("ROLLBACK");
				  mysql_close($mysql);
				  exit(1);
				  }
				}
			}
		$query = "INSERT INTO `payment`(`contract_id`, `timestamp`, `value_without_vat`, `cash`, `operator`, `notice`)
		      VALUES('$contract_id', '$pay_date', '$value_without_vat', '$cash', '$login', '$notice')";
		if(FALSE == mysql_query($query)){
		  $msg = "Error: ".mysql_error()." while executing ".$query;
		  header ("Location: show_error.php?error=".$msg);
		  mysql_query("ROLLBACK");
		  mysql_close($mysql);
		  exit(1);
		  }
		log_event($mysql, $client_id, "payment", mysql_insert_id($mysql) , "add", "New payment:
Contract_id: $contract_id
Payment date: $pay_date
Value: $value_without_vat
Cash: $cash
Operator: $login
Notice: $notice");
		mysql_query("COMMIT");
		header("Location: show_payment.php?client_id=".$client_id);
		exit(0);
    } // end of "create"
  // GET current rate
  $query = "select `rate` from `rate` where `date` = date(now())";
  $result = mysql_query($query);
  if($result == FALSE){
    $msg = "Error: ".mysql_error()." while executing ".$query;
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($result);
  if($n == 0){
    header("Location: add_rate.php?client_id=$client_id"."&amp;returnto=add_payment.php");
    mysql_close($mysql);
    exit(0);
    }
  $rate = mysql_result($result, 0, "rate");
  $query = "select
      id,
      c_type,
      c_number,
      description
      from contract
      where client_id = '$client_id'
			AND (expire_time = 0 OR expire_time > NOW())";
  $result = mysql_query($query);
  if($result == FALSE){
    $msg = "Error: ".mysql_error()." while executing ".$query;
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($result);
  if($n == 0){
    header("Location: show_contract.php?client_id=".$client_id);
    mysql_close($mysql);
    exit(0);
    }
?>
    <tr>
      <td valign="top" width="20%">
<? include("left_cl.php"); ?>
      </td>
      <td valign="top">
        <form method="POST">
        <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
          <tr bgcolor="lightgrey">
            <th>Догов╕р</th>
            <th>Дата платежу</th>
          </tr>
          <tr bgcolor="white">
            <td>
              <select name="contract_id">
<?
  for($i = 0; $i < $n; $i++){
    $id = mysql_result($result, $i, "id");
    $c_type = mysql_result($result, $i, "c_type");
    $c_number = mysql_result($result, $i, "c_number");
    $description = mysql_result($result, $i, "description");
    $len = strlen($c_type) + strlen($c_number) + strlen($description) + 15;
?>
                <option value="<? echo $id?>"><? echo $c_type." - ".$c_number.": ".$description?></option>
<?
    }
?>
              </select>
            </td>
            <td>
              <table>
                <tr>
                  <td>
                    <select name="year">
<?
  $today = getdate();
  $y1 = $today['year'];
  $m1 = $today['mon'];
  $d1 = $today['mday'];
  for($i = $y1 - 3; $i < $y1 + 2; $i++){
    if($y1 == $i) $s = "selected"; else $s = "";
?>
                      <option value="<?echo $i?>"<? if($s != "") echo " $s"; ?>><? echo $i?></option>
<?
    }
?>
                    </select>
                  </td>
                  <td>
                    <select name="month">
<?
  for($i = 1; $i <= 12; $i++){
    if($i == $m1) $s = "selected"; else $s = "";
?>
                      <option value="<? printf("%02d", $i);?>"<? if($s != "") echo " $s"; ?>><? echo strftime("%B",mktime(0,0,0,$i,1,date("Y")))?></option>
<?
    }
?>
                    </select>
                  </td>
                  <td>
                    <select name="day">
<?
  for($i = 1; $i <= 31; $i++){
    if($i == $d1) $s = "selected"; else $s = "";
?>
                      <option value="<? printf("%02d", $i);?>"<? if($s != "") echo " $s"; ?>><? echo $i; ?></option>
<?
    }
?>
                    </select>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
<?
	$query = "SELECT
			bill.id,
			UNIX_TIMESTAMP(bill.create_timestamp) AS create_timestamp,
			bill_value.to_order_with_vat,
			contract.c_type,
			contract.c_number
			FROM bill
			LEFT JOIN bill_value ON bill.id = bill_value.bill_id
			LEFT JOIN contract ON bill.contract_id = contract.id
			WHERE contract.client_id = '$client_id'
			AND bill.status = 'new'";
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
          <tr bgcolor="#BAE2FF">
						<td colspan="2">
							<table border="0">
							<caption>Оплата по рахунку</caption>
<?
	for($i = 0;$i < $n;$i++){
		$bill_id = mysql_result($result, $i, "bill.id");
		$create_timestamp = strftime("%d %B %Y", mysql_result($result, $i, "create_timestamp"));
		$to_order_with_vat = mysql_result($result, $i, "bill_value.to_order_with_vat");
		$c_type = mysql_result($result, $i, "contract.c_type");
		$c_number = mysql_result($result, $i, "contract.c_number");
?>
								<tr bgcolor="white">
									<td><input type="radio" name="bill_id" value="<? echo $bill_id; ?>" id="bill_id_<? echo $bill_id; ?>"></td>
									<td align="right" nowrap><label for="bill_id_<? echo $bill_id; ?>"><b><? echo number_format($to_order_with_vat, 2, ".", " ");?></b></label></td>
									<td align="right"><label for="bill_id_<? echo $bill_id; ?>"><? echo $c_type."-".$c_number; ?></label></td>
									<td align="right"><label for="bill_id_<? echo $bill_id; ?>"><? echo $create_timestamp; ?></label></td>
								</tr>
<?
		}
?>
							</table>
						</td>
          </tr>
					<tr bgcolor="#BAE2FF">
						<td colspan="2">
							<table border="0">
							<caption>Сума оплати вводиться в пол╕</caption>
      			  	<tr bgcolor="lightgrey">
									<th>&nbsp;</th>
      			  	  <th>Сума платежу без ПДВ</th>
      			  	  <th>╤нший плат╕ж</th>
      			  	</tr>
								<tr bgcolor="white">
									<td><input type="radio" name="bill_id" value="0" id="bill_id_0" checked></td>
            			<td align="right"><input type="text" name="sum" value="0"></td>
            			<td align="center"><input type="checkbox" name="cash"></td>
								</tr>
							</table>
						</td>
					</tr>
          <tr bgcolor="lightgrey">
            <th colspan="2">Нотатки</th>
          </tr>
          <tr bgcolor="white">
            <td colspan="2" align="right"><textarea name="notice" cols="<? echo $len; ?>" rows="5"></textarea></td>
          </tr>
          <tr bgcolor="white">
            <td colspan="2" align="right"><input type="submit" name="create" value="Створити..."></td>
          </tr>
        </table>
        </form>
      </td>
    </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
