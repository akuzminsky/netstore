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
  $action = mysql_escape_string($_GET["action"]);
	$bill_id = mysql_escape_string($_GET["bill_id"]);
	if($action == "delete"){
		delete_bill($mysql, $bill_id);
		log_event($mysql, $client_id, "bill", "$bill_id", "delete", "Removed bill id $bill_id");
		}
	$query = "select client.id,
    client.description,
    client.manager_id,
    client.person,
    client.phone,
    client.email,
    client.port,
    client.notes
    from client
    where client.id = '$client_id'";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
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
	if($action == "send2email"){
		$to_email = mysql_result($result, 0, "client.email");
		// Какого-то хрена после print_bill не выполняется log_event. Ошибок нет,
		// но и в таблице логов нифига не появляется...
		log_event($mysql, $client_id, "bill", $bill_id, "view", "Sent bill id $bill_id to $to_email");
		$f_path = print_bill($mysql, $bill_id, 1, "Invoice_".$bill_id.".pdf", "inline", false, true);
		mail_attach("Accounting Department <account@nbi.ua>", $to_email, "Invoice", 
"
Шановний абоненте.

Надсила╓мо Вам рахунок-фактуру  на оплату послуг ╤нтернет в╕д ". strftime("%d %B %Yр.")."
Просимо зд╕йснити оплату протягом трьох банк╕вських дн╕в.

З повагою,

--
Абонетський в╕дд╕л ВАТ \"Нац╕ональне Бюро ╤нформац╕╖\"
", $f_path, "Invoice_$bill_id.pdf");
		}
  $query = "SELECT
      bill.id,
      bill.bill_num,
      bill.contract_id,
      UNIX_TIMESTAMP(bill.timestamp) AS timestamp,
      UNIX_TIMESTAMP(bill.create_timestamp) AS create_timestamp,
      bill.operator,
      bill_value.to_order_without_vat,
      bill_value.to_order_vat,
      bill_value.to_order_with_vat,
      contract.client_id,
      contract.c_type,
      contract.c_number,
      contract.description,
			IFNULL(notification.id, 0) AS notification_id
      FROM contract
			LEFT JOIN bill ON contract.id = bill.contract_id
      LEFT JOIN bill_value ON bill_value.bill_id = bill.id
			LEFT JOIN notification ON notification.bill_id = bill.id
      where contract.client_id = '$client_id'
			HAVING bill.id IS NOT NULL
      order by bill.timestamp desc";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
	log_event($mysql, $client_id, "bill", "", "view", "Get invoices of the client id $client_id");
  $n = mysql_num_rows($result);
?>
      <tr>
				<td valign="top" width="20%"><? include("left_cl.php"); ?></td>
        <td valign="top">
          <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver" title="Рахунки">
          <caption>Рахунки</caption>
            <tr bgcolor="lightgrey">
              <th>Номер</th>
              <th>Пер╕од</th>
              <th>Дата виставлення</th>
              <th>Номер договору</th>
              <th>Сума до сплати без ПДВ</th>
              <th>ПДВ</th>
              <th>Сума до сплати з ПДВ</th>
              <th>Оператор</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
            </tr>
<?
  for($i = 0; $i < $n; $i++){
    $bill_id = mysql_result($result, $i, "bill.id");
    $bill_num = mysql_result($result, $i, "bill.bill_num");
    $notification_id = mysql_result($result, $i, "notification_id");
    $bill_timestamp = strftime("%OB %Y", mysql_result($result, $i, "timestamp"));
    $bill_create_timestamp = strftime("%d %B %Y", mysql_result($result, $i, "create_timestamp"));
    $contract_c_type = mysql_result($result, $i, "contract.c_type");
    $contract_c_number = mysql_result($result, $i, "contract.c_number");
    $contract_description = mysql_result($result, $i, "contract.description");
    $bill_operator = mysql_result($result, $i, "bill.operator");
    $to_order_with_vat = mysql_result($result, $i, "bill_value.to_order_with_vat");
    $to_order_vat = mysql_result($result, $i, "bill_value.to_order_vat");
    $to_order_without_vat = mysql_result($result, $i, "bill_value.to_order_without_vat");
?>
            <tr bgcolor="white" onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'">
              <td><? printf("%08u", $bill_num);?></td>
              <td nowrap><? echo $bill_timestamp;?></td>
              <td><? echo $bill_create_timestamp;?></td>
              <td><? echo $contract_c_type."-".$contract_c_number;?></td>
							<td align="right" nowrap><b><? echo number_format($to_order_without_vat, 2, ".", " ");?></b></td>
              <td align="right" nowrap><? echo number_format($to_order_vat, 2, ".", " ");?></td>
              <td align="right" nowrap><? echo number_format($to_order_with_vat, 2, ".", " ");?></td>
              <td><? echo $bill_operator;?></td>
              <td><a href="print_bill.php?bill_id=<? echo $bill_id;?>" target="_new">друк</a></td>
              <td><a href="show_bill.php?client_id=<? echo $client_id;?>&amp;bill_id=<? echo $bill_id;?>&amp;action=send2email">В╕д╕слати електронною поштою</a></td>
              <td>
<?
		if($notification_id != 0){
?>			
								<a href="print_notification.php?notification_id=<? echo $notification_id;?>" target="_new">пов╕домлення</a>
<?
			}
		else{
			echo "пов╕домлення";
			}
?>
							</td>
              <td><a href="show_bill.php?client_id=<? echo $client_id;?>&amp;bill_id=<? echo $bill_id;?>&amp;action=delete">видалити</a></td>
						</tr>
<?
    }
?>
            <tr bgcolor="white">
              <td colspan="10" align="right"><a href="add_bill.php?client_id=<? echo $client_id?>">Створити новий рахунок</a></td>
            </tr>
          </table>
        </td>
      </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
