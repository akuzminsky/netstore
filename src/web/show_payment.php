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
		session_timeout();
		}
  if(FALSE == mysql_select_db($db)){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $client_id = mysql_escape_string($_GET["client_id"]);
	$type = mysql_escape_string($_GET["type"]);
	
  $query = "SELECT client.id,
    	client.description,
    	client.manager_id,
    	client.person,
    	client.phone,
    	client.email,
    	client.port,
    	client.notes
    	FROM client
    	WHERE client.id = '$client_id'";
  $result = mysql_query($query);
  if($result == FALSE){
		$msg = "Error: ".mysql_error()." while executing ".$query;
		header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  $responsible_manager = mysql_result($result, 0, "client.manager_id");
  if($permlevel == 'manager'){
    $readonlystatus = "readonly";
    if($login != $responsible_manager){
      header($start_url);
      }
    }
  if($responsible_manager == ''){
    $responsible_manager = 'Нет';
    }
	if(!empty($_GET["action"]) && $_GET["action"] == "delete"){
		mysql_query("BEGIN");
		$id = mysql_escape_string($_GET["payment_id"]);
		$query = "delete from payment where id = '$id'";
		if(FALSE == mysql_query($query)){
			$msg = "Error: ".mysql_error()." while executing ".$query;
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
			mysql_close($mysql);
			exit(1);
			}
		log_event($mysql, $client_id, "payment", "$id", "delete", "Removed payment $id");
		mysql_query("COMMIT");
		}
  $query = "SELECT
			`payment`.`id`,
      `contract`.`c_type`,
      `contract`.`c_number`,
      `payment`.`timestamp`,
      `payment`.`value_without_vat`,
			`payment`.`operator`,
			`payment`.`notice`
      FROM `payment`
      LEFT JOIN `contract` on `contract`.`id` = `payment`.`contract_id`
      WHERE `contract`.`client_id` = '$client_id'";
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
	if($type == "other"){
		$query .= "AND `payment`.`cash` = 'yes'";
		}
	else{
		$query .= "AND `payment`.`cash` = 'no'";
		}
	$query .= " ORDER BY `payment`.`timestamp` DESC";
  $result = mysql_query($query);
  if($result == FALSE){
    $msg = "Error: ".mysql_error()." while executing ".$query;
		header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
	log_event($mysql, $client_id, "payment", "", "view", "Get payments of the client id $client_id\nType = $type");
  $n = mysql_num_rows($result);
?>
      <tr>
				<td valign="top" width="20%"><? include("left_cl.php"); ?></td>
        <td valign="top">
          <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver" title="Платеж╕">
          <caption>Платеж╕ кл╕╓нта</caption>
						<tr bgcolor="white">
							<td colspan="6" align="right">
								<a href="<? echo $_SERVER["PHP_SELF"]."?type=other&amp;client_id=".$client_id; ?>">╤нш╕ платеж╕</a>
							</td>
						</tr>
            <tr bgcolor="lightgrey">
              <th>Дата</th>
              <th>Номер договору</th>
              <th>Сума без ПДВ</th>
              <th>Оператор</th>
              <th>Нотатки</th>
              <th>&nbsp;</th>
            </tr>
<?
  for($i = 0; $i < $n; $i++){
    $pay_id = mysql_result($result, $i, "payment.id");
    $pay_timestamp = mysql_result($result, $i, "payment.timestamp");
    $pay_c_type = mysql_result($result, $i, "contract.c_type");
    $pay_c_number = mysql_result($result, $i, "contract.c_number");
    $pay_value_without_vat = mysql_result($result, $i, "payment.value_without_vat");
    $pay_operator = mysql_result($result, $i, "payment.operator");
    $pay_notice = mysql_result($result, $i, "payment.notice");
		$pay_notice = str_replace("\n", "<br>", $pay_notice);
?>
            <tr bgcolor="white" onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'">
              <td nowrap><? echo $pay_timestamp;?></td>
              <td><? echo $pay_c_type."-".$pay_c_number;?></td>
							<td align="right"><b><? echo number_format($pay_value_without_vat, 2, ".", ""); ?></b></td>
              <td><? echo $pay_operator;?></td>
              <td><? echo $pay_notice;?></td>
              <td><a href="show_payment.php?client_id=<? echo $client_id; ?>&amp;payment_id=<? echo $pay_id;?>&amp;action=delete">Видалити</a></td>
            </tr>
<?
    }
?>
            <tr bgcolor="white">
              <td colspan="6" align="right"><a href="add_payment.php?client_id=<? echo $client_id?>">Додати новий плат╕ж</a></td>
            </tr>
          </table>
        </td>
      </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
