<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
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
      && $permlevel != "topmanager"
			&& $permlevel != "manager"){
    header($start_url);
    }
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
  if($REQUEST_METHOD == "POST" && isset($_POST["print"])){
		print_bill($mysql, $_POST["cl_"], 2, "bills.pdf", "attachment", true, false);
		exit(0);
		}
  if($REQUEST_METHOD == "POST" && isset($_POST["mail"])){
		$query = "SELECT
        bill.id,
        bill.bill_num,
        client.email
        FROM bill
				LEFT JOIN contract ON contract.id = bill.contract_id
        LEFT JOIN client ON client.id = contract.client_id";
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
    $emails = array();
    for($i = 0; $i < $n; $i++){
      $bill_id = mysql_result($result, $i, "bill.id");
      $bill_num = mysql_result($result, $i, "bill.bill_num");
      $emails[$bill_id] = mysql_result($result, $i, "client.email");
			}
		foreach($_POST["cl_"] as $bill_id){
			$f_path = print_bill($mysql, $bill_id, 1, "Invoice_".$bill_id.".pdf", "inline", false, true);
			$to_email = $emails[$bill_id];
			echo "sending invoice $bill_id to address $to_email<br>";
			mail_attach("Accounting Department <account@nbi.ua>", $to_email, "Invoice number $bill_num",
"
Шановний абоненте.

Надсила╓мо Вам рахунок-фактуру  на оплату послуг ╤нтернет # $bill_num в╕д ". strftime("%d %B %Yр.")."
Просимо зд╕йснити оплату протягом трьох банк╕вських дн╕в.

З повагою,

--
Абонетський в╕дд╕л ВАТ \"Нац╕ональне Бюро ╤нформац╕╖\"
", $f_path, "Invoice_$bill_id.pdf");
			}
		header("Location: rep_bill.php");
		exit(0);
		}
  if($REQUEST_METHOD == "POST" && isset($_POST["print_notification"])){
		$notifications = array();
    $yn = mysql_escape_string($_POST["yn"]);
    $mn = mysql_escape_string($_POST["mn"]);
    $dn = mysql_escape_string($_POST["dn"]);
		foreach($_POST["cl_"] as $bill_id){
			$notifications[] = add_notification($mysql, $bill_id, $yn, $mn, $dn);
			}
		print_notification($mysql, $notifications, 2, "Notifications_".sprintf("%04u", $y)."-".sprintf("%02u", $m).".pdf", "attachment", true);
		exit(0);
		}
  readfile("$DOCUMENT_ROOT/head.html");
  include("top.php");
	
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
    if($y2 == "") $y2 = $y1;
    if($m2 == "") $m2 = $m1;
    if($d2 == "") $d2 = date("d");
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
    $type = mysql_escape_string($_GET["other"]);
		
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
        <table>
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
  $query = "SELECT
			bill.id,
			bill.bill_num,
			UNIX_TIMESTAMP(bill.create_timestamp) AS timestamp,
			contract.id,
			contract.c_type,
			contract.c_number,
			client.id,
			client.description,
			client.manager_id,
			bill.operator,
			bill_value.to_order_with_vat
			FROM bill
			LEFT JOIN bill_value ON bill_value.bill_id = bill.id
			LEFT JOIN contract ON contract.id = bill.contract_id
			LEFT JOIN client ON client.id = contract.client_id
			WHERE 1
			AND bill.create_timestamp >= '$y1-$m1-$d1'
      AND bill.create_timestamp <= '$y2-$m2-$d2'";
  if($permlevel == "manager"){
    $query .= " AND client.manager_id = '$login'";
    }
	/*
  if($order == ""){
    $order = "client.description";
    }
  if($dir == ""){
    $dir = "ASC";
    }
	*/
	if($order != ""){
  	$query .= " ORDER BY $order $dir";
		}
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
				<form name="bill_form" method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
        <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver" width="100%">
        <caption>Рахунки, виставлен╕ за пер╕од з <b><? echo strftime("%d %B %Y р.", $dfrom);?></b> по <b><? echo strftime("%d %B %Y р.", $dto);?></b></caption>
        <tr bgcolor="#EDEDED">
          <th><input type="checkbox" name="check_all" onClick = "return setAllCheckboxes('bill_form');"></th>
          <th>Номер рахунку</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.description&amp;dir=".urlencode($dir).$url_ts?>">Кл╕╓нт</a></th>
          <th>Номер Договора</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=bill.create_timestamp&amp;dir=".urlencode($dir).$url_ts?>">Дата виставлення</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=bill_value.to_order_with_vat&amp;dir=".urlencode($dir).$url_ts?>">Сума</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=bill.operator&amp;dir=".urlencode($dir).$url_ts?>">Оператор</a></th>
        </tr>
<?
	$color1 ="#FFF4AB";
  $color2 = "white";
	for($i = 0; $i < $n; $i++){
    $bill_id = mysql_result($result, $i, "bill.id");
    $bill_num = mysql_result($result, $i, "bill.bill_num");
    $bill_create_timestamp = mysql_result($result, $i, "timestamp");
    $contract_id = mysql_result($result, $i, "contract.id");
    $description = mysql_result($result, $i, "client.description");
    $c_type = mysql_result($result, $i, "contract.c_type");
    $c_number = mysql_result($result, $i, "contract.c_number");
    $to_order_with_vat = mysql_result($result, $i, "bill_value.to_order_with_vat");
    $operator = mysql_result($result, $i, "bill.operator");
    $client_id = mysql_result($result, $i, "client.id");
		$sum = round2($sum + $to_order_with_vat);
		$color = ($color == $color1) ? $color2: $color1;
?>
          <tr bgcolor="<? echo $color; ?>">
						<td><input type="checkbox" name="cl_[]" value="<? echo $bill_id; ?>"></td>
            <td align="right"><a href="print_bill.php?bill_id=<? echo $bill_id?>" target="_new"><? printf("%08u",$bill_num);?></a></td>
            <td><a href="show_client.php?client_id=<? echo $client_id;?>"><? echo "$description";?></a></td>
            <td><a href="show_contract.php?client_id=<? echo $client_id;?>"><? echo $c_type."-".$c_number;?></a></td>
            <td align="right"><? echo strftime("%d %B %Y", $bill_create_timestamp);?></td>
            <td align="right" nowrap><b><? echo number_format($to_order_with_vat, 2, ".", " ");?></b></td>
            <td><? echo $operator;?></td>
          </tr>
<?
    }
?>
          <tr bgcolor="white">
            <td align="right" colspan="5">Всього</td>
            <td align="right" nowrap><b><? echo number_format($sum, 2, ".", " ");?></b></td>
            <td>&nbsp;</td>
          </tr>
					<tr bgcolor="<? echo $color2; ?>">
						<td colspan="7" align="center">
							<input type="submit" name="print" value="Надрукувати рахунки">
							<input type="submit" name="mail" value="В╕д╕слати рахунки електронною поштою">
						</td>
					</tr>
					<tr bgcolor="<? echo $color2; ?>">
						<td colspan="7" align="center">
							<select name="dn">
<?
	$dn = date("j");
	for($j = 1; $j <= 31; $j++){
?>
								<option <? if($j == $dn){ echo "selected"; }?>><? echo $j;?></option>
<?
		}
?>
							</select>
							<select name="mn">
<?
	$mn = date("n");
	for($j = 1; $j <= 12; $j++){
?>
								<option <? if($j == $mn){ echo "selected"; }?> value="<? echo $j;?>"><? echo strftime("%B", mktime(0, 0, 0, $j, 1, 2000));?></option>
<?
		}
?>
							</select>
							<select name="yn">
<?
	$yn = date("Y");
	for($j = $yn - 3; $j <= $y + 5; $j++){
?>
								<option <? if($j == $yn){ echo "selected"; }?>><? echo $j;?></option>
<?
		}
?>
								</select>																																																												 
						<input type="submit" name="print_notification" value="Надрукувати пов╕домлення"></td>
					</tr>
        </table>
      </td>
    </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
