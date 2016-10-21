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
		print_stwa($mysql, $_POST["cl_"], 2, "Statements_of_work_agreement.pdf", "attachment", true);
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
			stwa.id,
			stwa.stwa_num,
			UNIX_TIMESTAMP(stwa.create_time) AS timestamp,
			contract.id,
			contract.c_type,
			contract.c_number,
			client.id,
			client.description,
			client.manager_id,
			stwa.value_without_vat
			FROM stwa
			LEFT JOIN contract ON contract.id = stwa.contract_id
			LEFT JOIN client ON client.id = contract.client_id
			WHERE 1
			AND stwa.create_time >= '$y1-$m1-$d1'
      AND stwa.create_time <= '$y2-$m2-$d2'";
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
				<form name="stwa_form" method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
        <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver" width="100%">
        <caption>Акти виконаних роб╕т, створен╕ за пер╕од з <b><? echo strftime("%d %B %Y р.", $dfrom);?></b> по <b><? echo strftime("%d %B %Y р.", $dto);?></b></caption>
        <tr bgcolor="#EDEDED">
          <th><input type="checkbox" name="check_all" onClick = "return setAllCheckboxes('stwa_form');"></th>
          <th>Номер акта</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.description&amp;dir=".urlencode($dir).$url_ts?>">Кл╕╓нт</a></th>
          <th>Номер Договора</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=stwa.create_time&amp;dir=".urlencode($dir).$url_ts?>">Дата створення</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=stwa.value_without_vat&amp;dir=".urlencode($dir).$url_ts?>">Сума</a></th>
        </tr>
<?
	$color1 ="#FFF4AB";
  $color2 = "white";
	for($i = 0; $i < $n; $i++){
    $stwa_id = mysql_result($result, $i, "stwa.id");
    $stwa_num = mysql_result($result, $i, "stwa.stwa_num");
    $stwa_create_timestamp = mysql_result($result, $i, "timestamp");
    $contract_id = mysql_result($result, $i, "contract.id");
    $description = mysql_result($result, $i, "client.description");
    $c_type = mysql_result($result, $i, "contract.c_type");
    $c_number = mysql_result($result, $i, "contract.c_number");
    $value_without_vat = mysql_result($result, $i, "stwa.value_without_vat");
    $client_id = mysql_result($result, $i, "client.id");
		$sum = round2($sum + $value_without_vat);
		$color = ($color == $color1) ? $color2: $color1;
?>
          <tr bgcolor="<? echo $color; ?>">
						<th><input type="checkbox" name="cl_[]" value="<? echo $stwa_id; ?>"></th>
            <td align="right"><a href="print_stwa.php?stwa_id=<? echo $stwa_id?>" target="_new"><? printf("%08u",$stwa_num);?></a></td>
            <td><a href="show_client.php?client_id=<? echo $client_id;?>"><? echo "$description";?></a></td>
            <td><a href="show_contract.php?client_id=<? echo $client_id;?>"><? echo $c_type."-".$c_number;?></a></td>
            <td align="right"><? echo strftime("%d %B %Y", $stwa_create_timestamp);?></td>
            <td align="right" nowrap><b><? echo number_format($value_without_vat, 2, ".", " ");?></b></td>
          </tr>
<?
    }
?>
          <tr bgcolor="white">
            <td align="right" colspan="5">Всього</td>
            <td align="right" nowrap><b><? echo number_format($sum, 2, ".", " ");?></b></td>
          </tr>
					<tr bgcolor="<? echo $color2; ?>">
						<td colspan="6" align="center"><input type="submit" name="print" value="Надрукувати"></td>
					</tr>
        </table>
      </td>
    </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
