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
    $type = mysql_escape_string($_POST["type"]);
		
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
	$vat = get_vat($mysql);
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
			client.id,
			client.description,
			cluster.id,
			cluster.description,
			contract.id,
			contract.c_type,
			contract.c_number,
			UNIX_TIMESTAMP(payment.timestamp) AS timestamp,
			payment.value_without_vat,
			payment.operator,
			payment.notice,
			payment.cash
			FROM payment
			LEFT JOIN contract ON contract.id = payment.contract_id
			LEFT JOIN client ON client.id = contract.client_id
			LEFT JOIN client_cluster ON client_cluster.client_id = client.id
			LEFT JOIN cluster ON cluster.id = client_cluster.cluster_id
			WHERE cluster.id IS NOT NULL
			AND payment.timestamp >= '$y1-$m1-$d1'
      AND payment.timestamp <= '$y2-$m2-$d2'";
  if($permlevel == "manager"){
    $query .= " AND client.manager_id = '$login'";
    }
  if($type == "other"){
    $query .= " AND payment.cash = 'yes'";
    }
	else{
		$query .= " AND payment.cash = 'no'";
		}
  if($order == ""){
    $order = "cluster.description";
    }
  if($dir == ""){
    $dir = "ASC";
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
        <caption>Зв╕т по оплатам кл╕╓нт╕в б╕знес-центр╕в за пер╕од з <b><? echo strftime("%d %B %Y р.", $dfrom);?></b> по <b><? echo strftime("%d %B %Y р.", $dto);?></b></caption>
        <tr bgcolor="#EDEDED">
          <th># п/п</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.description&amp;dir=".urlencode($dir).$url_ts?>">Назва кл╕╓нта</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=cluster.description&amp;dir=".urlencode($dir).$url_ts?>">Б╕знес-центр</a></th>
          <th>Номер Договора</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=payment.timestamp&amp;dir=".urlencode($dir).$url_ts?>">Дата проплати</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=payment.value_without_vat&amp;dir=".urlencode($dir).$url_ts?>">Сума без ПДВ</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=payment.value_without_vat&amp;dir=".urlencode($dir).$url_ts?>">Сума з ПДВ</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=payment.operator&amp;dir=".urlencode($dir).$url_ts?>">Оператор</a></th>
          <th>Нотатки</th>
        </tr>
<?
	$sum = 0;
	$subtotal = 0;
	$color1 ="#FFF4AB";
	$color2 = "white";
  for($i = 0; $i < $n; $i++){
    $client_id = mysql_result($result, $i, "client.id");
    $cluster_id = mysql_result($result, $i, "cluster.id");
    $contract_id = mysql_result($result, $i, "contract.id");
    $description = mysql_result($result, $i, "client.description");
    $bc_description = mysql_result($result, $i, "cluster.description");
    $c_type = mysql_result($result, $i, "contract.c_type");
    $c_number = mysql_result($result, $i, "contract.c_number");
    $timestamp = mysql_result($result, $i, "timestamp");
    $value = mysql_result($result, $i, "payment.value_without_vat");
    $value_with_vat = round2($value * (1 + $vat));
    $operator = mysql_result($result, $i, "payment.operator");
    $notice = mysql_result($result, $i, "payment.notice");
		$sum = round($sum + $value, 2);
		$subtotal = round($subtotal + $value, 2);
		$color = ($color == $color1) ? $color2: $color1;
?>
          <tr bgcolor="<? echo $color; ?>">
            <td align="right"><? echo $i + 1;?></td>
            <td><a href="show_client.php?client_id=<? echo $client_id;?>"><? echo "$description";?></a></td>
            <td><a href="show_ll.php?cluster_id=<? echo $cluster_id;?>"><? echo "$bc_description";?></a></td>
            <td><a href="show_contract.php?client_id=<? echo $client_id;?>"><? echo $c_type."-".$c_number;?></a></td>
            <td align="right"><? echo strftime("%d %B %Y", $timestamp);?></td>
            <td align="right" nowrap><b><? echo number_format($value, 2, ".", " ");?></b></td>
            <td align="right" nowrap><b><? echo number_format($value_with_vat, 2, ".", " ");?></b></td>
            <td><? echo $operator;?></td>
            <td><? echo $notice;?></td>
          </tr>
<?
		// Print subtotal
		if($i == ($n - 1) || ($cluster_id != @mysql_result($result, $i + 1, "cluster.id"))){
?>
          <tr bgcolor="#999000">
						<td align="right" colspan="5">Всього по б╕знес-центру&nbsp;<b><? echo $bc_description; ?></b>:</td>
            <td align="right" nowrap><b><? echo number_format($subtotal, 2, ".", " ");?></b></td>
            <td colspan="3">&nbsp;</td>
          </tr>
<?
			$subtotal = 0;
			} // subtotal
		}
?>
          <tr bgcolor="white">
            <td align="right" colspan="5">Всього:</td>
            <td align="right" nowrap><b><? echo number_format($sum, 2, ".", " ");?></b></td>
            <td colspan="3">&nbsp;</td>
          </tr>
        </table>
      </td>
    </tr>
		<tr>
			<td><hr></td>
		</tr>
<?
  $query = "SELECT
			client.id,
			client.description,
			cluster.id,
			cluster.description,
			contract.id,
			contract.c_type,
			contract.c_number,
			UNIX_TIMESTAMP(payment.timestamp) AS timestamp,
			payment.value_without_vat,
			payment.operator,
			payment.notice,
			payment.cash
			FROM payment
			LEFT JOIN contract ON contract.id = payment.contract_id
			LEFT JOIN client ON client.id = contract.client_id
			LEFT JOIN client_cluster ON client_cluster.client_id = client.id
			LEFT JOIN cluster ON cluster.id = client_cluster.cluster_id
			WHERE cluster.id IS NULL
			AND payment.timestamp >= '$y1-$m1-$d1'
      AND payment.timestamp <= '$y2-$m2-$d2'";
  if($permlevel == "manager"){
    $query .= " AND client.manager_id = '$login'";
    }
  if($type == "other"){
    $query .= " AND payment.cash = 'yes'";
    }
	else{
		$query .= " AND payment.cash = 'no'";
		}
  if($order == ""){
    $order = "cluster.description";
    }
  if($dir == ""){
    $dir = "ASC";
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
        <caption>Зв╕т по оплатам кл╕╓нт╕в вид╕лених л╕н╕й за пер╕од з <b><? echo strftime("%d %B %Y р.", $dfrom);?></b> по <b><? echo strftime("%d %B %Y р.", $dto);?></b></caption>
        <tr bgcolor="#EDEDED">
          <th># п/п</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.description&amp;dir=".urlencode($dir).$url_ts?>">Назва кл╕╓нта</a></th>
          <th>Номер Договора</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=payment.timestamp&amp;dir=".urlencode($dir).$url_ts?>">Дата проплати</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=payment.value_without_vat&amp;dir=".urlencode($dir).$url_ts?>">Сума без ПДВ</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=payment.value_without_vat&amp;dir=".urlencode($dir).$url_ts?>">Сума з ПДВ</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=payment.operator&amp;dir=".urlencode($dir).$url_ts?>">Оператор</a></th>
          <th>Нотатки</th>
        </tr>
<?
	$sum = 0;
	$color1 ="#FFF4AB";
	$color2 = "white";
  for($i = 0; $i < $n; $i++){
    $client_id = mysql_result($result, $i, "client.id");
    $contract_id = mysql_result($result, $i, "contract.id");
    $description = mysql_result($result, $i, "client.description");
    $c_type = mysql_result($result, $i, "contract.c_type");
    $c_number = mysql_result($result, $i, "contract.c_number");
    $timestamp = mysql_result($result, $i, "timestamp");
    $value = mysql_result($result, $i, "payment.value_without_vat");
		$value_with_vat = round2($value * (1 + $vat));
    $operator = mysql_result($result, $i, "payment.operator");
    $notice = mysql_result($result, $i, "payment.notice");
		$sum = round($sum + $value, 2);
		$color = ($color == $color1) ? $color2: $color1;
?>
          <tr bgcolor="<? echo $color; ?>">
            <td align="right"><? echo $i + 1;?></td>
            <td><a href="show_client.php?client_id=<? echo $client_id;?>"><? echo "$description";?></a></td>
            <td><a href="show_contract.php?client_id=<? echo $client_id;?>"><? echo $c_type."-".$c_number;?></a></td>
            <td align="right"><? echo strftime("%d %B %Y", $timestamp);?></td>
            <td align="right" nowrap><b><? echo number_format($value, 2, ".", " ");;?></b></td>
            <td align="right" nowrap><b><? echo number_format($value_with_vat, 2, ".", " ");;?></b></td>
            <td><? echo $operator;?></td>
            <td><? echo $notice;?></td>
          </tr>
<?
    }
?>
          <tr bgcolor="white">
            <td align="right" colspan="4">Всього:</td>
            <td align="right" nowrap><b><? echo number_format($sum, 2, ".", " ");?></b></td>
            <td colspan="3">&nbsp;</td>
          </tr>
        </table>
      </td>
    </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
