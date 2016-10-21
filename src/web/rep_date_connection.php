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
		
		$dfrom = mktime(0, 0, 0, $m1, $d1, $y1);
		$dto = mktime(0, 0, 0, $m2, $d2, $y2);
    }
	$url_ts = "&amp;y1=$y1&amp;m1=$m1&amp;d1=$d1&amp;y2=$y2&amp;m2=$m2&amp;d2=$d2";
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
        </form>
      </td>
    </tr>
<?
  $query = "SELECT
			cluster.id,
			cluster.description,
      client.id,
			client.manager_id,
      client.description,
      UNIX_TIMESTAMP(client.activation_time) AS activation_time,
			UNIX_TIMESTAMP(MIN(service.start_time)) AS service_start
      FROM client
			LEFT JOIN client_cluster ON client.id = client_cluster.client_id
			LEFT JOIN cluster ON cluster.id = client_cluster.cluster_id
			LEFT JOIN contract ON contract.client_id = client.id
			LEFT JOIN service ON service.contract_id = contract.id
			WHERE cluster.id IS NOT NULL
			AND client.activation_time >= '$y1-$m1-$d1'
      AND client.activation_time <= '$y2-$m2-$d2'
			GROUP BY client.id";
  if($permlevel == "manager"){
    $query .= " AND client.manager_id = '$login'";
    }
  if($order == ""){
    $order = "client.description";
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
        <caption>Список кл╕╓нт╕в, п╕дключених в б╕знес-центрах за пер╕од з <b><? echo strftime("%d %B %Y р.", $dfrom);?></b> по <b><? echo strftime("%d %B %Y р.", $dto);?></b></caption>
        <tr bgcolor="#EDEDED">
          <th># п/п</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.description&amp;dir=".urlencode($dir).$url_ts?>">Назва кл╕╓нта</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=cluster.description&amp;dir=".urlencode($dir).$url_ts?>">Б╕знес-центр</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.activation_time&amp;dir=".urlencode($dir).$url_ts?>">Дата п╕дключення</a></th>
          <th>Початок послуг</th>
        </tr>
<?
  for($i = 0; $i < $n; $i++){
    $client_id = mysql_result($result, $i, "client.id");
    $cluster_id = mysql_result($result, $i, "cluster.id");
    $bc_description = htmlspecialchars(mysql_result($result, $i, "cluster.description"), ENT_QUOTES, "KOI8-R");
    $description = htmlspecialchars(mysql_result($result, $i, "client.description"), ENT_QUOTES, "KOI8-R");
    $activation_time = mysql_result($result, $i, "activation_time");
    $service_start = mysql_result($result, $i, "service_start");
?>
          <tr bgcolor="white">
            <td align="right"><? echo $i + 1;?></td>
            <td><a href="show_client.php?client_id=<? echo $client_id;?>"><? echo "$description";?></a></td>
            <td><a href="show_ll.php?cluster_id=<? echo $cluster_id;?>"><? echo "$bc_description";?></a></td>
            <td align="right"><? echo strftime("%d %B %Y", $activation_time);?></td>
						<td align="right"><? echo $service_start == 0 ? "<font color=\"red\">Послуги в╕дсутн╕</font>" : strftime("%d %B %Y", $service_start);?></td>
          </tr>
<?
    }
?>
        </table>
      </td>
    </tr>
		<tr>
			<td><hr></td>
		</tr>
<?
  $query = "SELECT
			cluster.id,
			cluster.description,
			client.manager_id,
      SUM(IF(
						client.activation_time >= '$y1-$m1-$d1'
			      AND client.activation_time <= '$y2-$m2-$d2', 1, 0)) AS num,
      SUM(IF(
						client.inactivation_time >= '$y1-$m1-$d1'
			      AND client.inactivation_time <= '$y2-$m2-$d2', 1, 0)) AS num_off
			
      FROM client
			LEFT JOIN client_cluster ON client.id = client_cluster.client_id
			LEFT JOIN cluster ON cluster.id = client_cluster.cluster_id
			WHERE (client.activation_time >= '$y1-$m1-$d1'
      			AND client.activation_time <= '$y2-$m2-$d2')
			OR (client.inactivation_time >= '$y1-$m1-$d1'
					AND client.inactivation_time <= '$y2-$m2-$d2')
			GROUP BY cluster.id
			HAVING cluster.id IS NOT NULL";
  if($permlevel == "manager"){
    $query .= " AND client.manager_id = '$login'";
    }
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
      <td>
        <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver" width="100%">
        <caption>Статистичн╕ дан╕ по б╕знес-центрам за пер╕од з <b><? echo strftime("%d %B %Y р.", $dfrom);?></b> по <b><? echo strftime("%d %B %Y р.", $dto);?></b></caption>
        <tr bgcolor="#EDEDED">
          <th># п/п</th>
          <th>Б╕знес-центр</th>
          <th>К╕льк╕сть п╕дключених кл╕╓нт╕в</th>
          <th>К╕льк╕сть вибувших кл╕╓нт╕в</th>
        </tr>
<?
	$sum_connected = 0;
	$sum_disconnected = 0;
  for($i = 0; $i < $n; $i++){
    $cluster_id = mysql_result($result, $i, "cluster.id");
    $bc_description = htmlspecialchars(mysql_result($result, $i, "cluster.description"), ENT_QUOTES, "KOI8-R");
    $num = mysql_result($result, $i, "num");
    $num_off = mysql_result($result, $i, "num_off");
		$sum_connected += $num;
		$sum_disconnected += $num_off;
?>
          <tr bgcolor="white">
            <td align="right"><? echo $i + 1;?></td>
            <td><a href="show_ll.php?cluster_id=<? echo $cluster_id;?>"><? echo "$bc_description";?></a></td>
            <td align="right"><? echo $num;?></td>
            <td align="right"><? echo $num_off;?></td>
          </tr>
<?
    }
?>
          <tr bgcolor="white">
            <th align="right" colspan="2">Всього:</th>
            <th align="right"><? echo $sum_connected; ?></th>
            <th align="right"><? echo $sum_disconnected; ?></th>
          </tr>
        </table>
      </td>
    </tr>
		<tr>
			<td><hr></td>
		</tr>
<?
	// Report "Gone customers"
	mysql_query("BEGIN");
  $query = "SELECT
			cluster.id,
			cluster.description,
      client.id,
      client.description,
			client.manager_id,
      UNIX_TIMESTAMP(client.activation_time) AS activation_time,
      UNIX_TIMESTAMP(client.inactivation_time) AS inactivation_time
      FROM client
			LEFT JOIN client_cluster ON client.id = client_cluster.client_id
			LEFT JOIN cluster ON cluster.id = client_cluster.cluster_id
			WHERE cluster.id IS NOT NULL
			AND client.inactivation_time >= '$y1-$m1-$d1'
      AND client.inactivation_time <= '$y2-$m2-$d2'";
  if($permlevel == "manager"){
    $query .= " AND client.manager_id = '$login'";
    }
  if($order == ""){
    $order = "client.description";
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

	// Get saldo of gone customers
	// Get sum of charges
	$q = "SELECT
			client.id,
			SUM(charge.value_without_vat) AS charged
			FROM client
			LEFT JOIN contract ON contract.client_id = client.id
			LEFT JOIN service ON service.contract_id = contract.id
			LEFT JOIN charge ON charge.service_id = service.id
			WHERE 1
			AND client.inactivation_time >= '$y1-$m1-$d1'
			AND client.inactivation_time <= '$y2-$m2-$d2'
			GROUP BY client.id";
  $r = mysql_query($q);
  if($r == FALSE){
    $msg = "Error: ".mysql_error()." while executing:\n".$q;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
		}
	$k = mysql_num_rows($r);
	$charges = array();
	for($i = 0; $i < $k; $i++){
		$client_id = mysql_result($r, $i, "client.id");
		$charges[$client_id] = mysql_result($r, $i, "charged");
		}
	// Get sum of payments
	$q = "SELECT
			client.id,
			SUM(payment.value_without_vat) AS paid
			FROM client
			LEFT JOIN contract ON contract.client_id = client.id
			LEFT JOIN payment ON payment.contract_id = contract.id
			WHERE 1 
			AND client.inactivation_time >= '$y1-$m1-$d1'
			AND client.inactivation_time <= '$y2-$m2-$d2'
			GROUP BY client.id";
  $r = mysql_query($q);
  if($r == FALSE){
    $msg = "Error: ".mysql_error()." while executing:\n".$q;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
		}
	$k = mysql_num_rows($r);
	$payments = array();
	for($i = 0; $i < $k; $i++){
		$client_id = mysql_result($r, $i, "client.id");
		$payments[$client_id] = mysql_result($r, $i, "paid");
		}
	mysql_query("COMMIT");
?>
    <tr>
      <td>
        <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver" width="100%">
        <caption>Список вибувших кл╕╓нт╕в, що були п╕дключен╕ у б╕знес-центрах, за пер╕од з <b><? echo strftime("%d %B %Y р.", $dfrom);?></b> по <b><? echo strftime("%d %B %Y р.", $dto);?></b></caption>
        <tr bgcolor="#EDEDED">
          <th># п/п</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=cluster.description&amp;dir=".urlencode($dir).$url_ts?>">Б╕знес-центр</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.description&amp;dir=".urlencode($dir).$url_ts?>">Назва кл╕╓нта</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.activation_time&amp;dir=".urlencode($dir).$url_ts?>">Дата п╕дключення</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.inactivation_time&amp;dir=".urlencode($dir).$url_ts?>">Дата в╕дключення</a></th>
          <th>Сальдо</th>
        </tr>
<?
  for($i = 0; $i < $n; $i++){
    $client_id = mysql_result($result, $i, "client.id");
    $cluster_id = mysql_result($result, $i, "cluster.id");
    $description = htmlspecialchars(mysql_result($result, $i, "client.description"), ENT_QUOTES, "KOI8-R");
    $cluster_description = htmlspecialchars(mysql_result($result, $i, "cluster.description"), ENT_QUOTES, "KOI8-R");
    $inactivation_time = mysql_result($result, $i, "inactivation_time");
    $activation_time = mysql_result($result, $i, "activation_time");
		$saldo = round2($payments[$client_id] - $charges[$client_id]);
?>
          <tr bgcolor="white">
            <td align="right"><? echo $i + 1;?></td>
            <td><a href="show_ll.php?cluster_id=<? echo $cluster_id;?>"><? echo "$cluster_description";?></a></td>
            <td><a href="show_client.php?client_id=<? echo $client_id;?>"><? echo "$description";?></a></td>
            <td align="right"><? echo strftime("%d %B %Y", $activation_time);?></td>
            <td align="right"><? echo strftime("%d %B %Y", $inactivation_time);?></td>
            <td align="right" nowrap><? echo number_format($saldo, 2, ".", " ");?></td>
          </tr>
<?
    }
?>
        </table>
      </td>
    </tr>
<?
  $query = "SELECT
			cluster.id,
			cluster.description,
      client.id,
      client.description,
      UNIX_TIMESTAMP(client.activation_time) AS activation_time,
			UNIX_TIMESTAMP(MIN(service.start_time)) AS service_start
      FROM client
			LEFT JOIN client_cluster ON client.id = client_cluster.client_id
			LEFT JOIN cluster ON cluster.id = client_cluster.cluster_id
			LEFT JOIN contract ON contract.client_id = client.id
			LEFT JOIN service ON service.contract_id = contract.id
			WHERE cluster.id IS NULL
			AND client.activation_time >= '$y1-$m1-$d1'
      AND client.activation_time <= '$y2-$m2-$d2'
			GROUP BY client.id";
  if($permlevel == "manager"){
    $query .= " AND client.manager_id = '$login'";
    }
  if($order == ""){
    $order = "client.description";
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
        <caption>Список кл╕╓нт╕в, п╕дключених через вид╕лену л╕н╕ю за пер╕од з <b><? echo strftime("%d %B %Y р.", $dfrom);?></b> по <b><? echo strftime("%d %B %Y р.", $dto);?></b></caption>
        <tr bgcolor="#EDEDED">
          <th># п/п</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.description&amp;dir=".urlencode($dir).$url_ts?>">Назва кл╕╓нта</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.activation_time&amp;dir=".urlencode($dir).$url_ts?>">Дата п╕дключення</a></th>
          <th>Початок послуг</th>
        </tr>
<?
  for($i = 0; $i < $n; $i++){
    $client_id = mysql_result($result, $i, "client.id");
    $description = htmlspecialchars(mysql_result($result, $i, "client.description"), ENT_QUOTES, "KOI8-R");
    $activation_time = mysql_result($result, $i, "activation_time");
		$service_start = mysql_result($result, $i, "service_start");
?>
          <tr bgcolor="white">
            <td align="right"><? echo $i + 1;?></td>
            <td><a href="show_client.php?client_id=<? echo $client_id;?>"><? echo "$description";?></a></td>
            <td align="right"><? echo strftime("%d %B %Y", $activation_time);?></td>
						<td align="right"><? echo $service_start == 0 ? "<font color=\"red\">Послуги в╕дсутн╕</font>" : strftime("%d %B %Y", $service_start);?></td>
          </tr>
<?
    }
?>
        </table>
      </td>
    </tr>
<?
  $query = "SELECT
			cluster.id,
			cluster.description,
      client.id,
      client.description,
      UNIX_TIMESTAMP(client.activation_time) AS activation_time,
      UNIX_TIMESTAMP(client.inactivation_time) AS inactivation_time
      FROM client
			LEFT JOIN client_cluster ON client.id = client_cluster.client_id
			LEFT JOIN cluster ON cluster.id = client_cluster.cluster_id
			WHERE cluster.id IS NULL
			AND client.inactivation_time >= '$y1-$m1-$d1'
      AND client.inactivation_time <= '$y2-$m2-$d2'";
  if($permlevel == "manager"){
    $query .= " AND client.manager_id = '$login'";
    }
  if($order == ""){
    $order = "client.description";
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
        <caption>Список вибувших кл╕╓нт╕в, що були п╕дключен╕ через вид╕лену л╕н╕ю, за пер╕од з <b><? echo strftime("%d %B %Y р.", $dfrom);?></b> по <b><? echo strftime("%d %B %Y р.", $dto);?></b></caption>
        <tr bgcolor="#EDEDED">
          <th># п/п</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.description&amp;dir=".urlencode($dir).$url_ts?>">Назва кл╕╓нта</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.activation_time&amp;dir=".urlencode($dir).$url_ts?>">Дата п╕дключення</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.inactivation_time&amp;dir=".urlencode($dir).$url_ts?>">Дата в╕дключення</a></th>
          <th>Сальдо</th>
        </tr>
<?
  for($i = 0; $i < $n; $i++){
    $client_id = mysql_result($result, $i, "client.id");
    $description = htmlspecialchars(mysql_result($result, $i, "client.description"), ENT_QUOTES, "KOI8-R");
    $activation_time = mysql_result($result, $i, "activation_time");
    $inactivation_time = mysql_result($result, $i, "inactivation_time");
		$saldo = round2($payments[$client_id] - $charges[$client_id]);
?>
          <tr bgcolor="white">
            <td align="right"><? echo $i + 1;?></td>
            <td><a href="show_client.php?client_id=<? echo $client_id;?>"><? echo "$description";?></a></td>
            <td align="right"><? echo strftime("%d %B %Y", $activation_time);?></td>
            <td align="right"><? echo strftime("%d %B %Y", $inactivation_time);?></td>
						<td align="right" nowrap><? echo number_format($saldo, 2, ".", " ");?></td>
          </tr>
<?
    }
?>
        </table>
      </td>
    </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
