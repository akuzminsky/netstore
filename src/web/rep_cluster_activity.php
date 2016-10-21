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
	$in = 0;
	$out = 0;
	$num = 0;
	if($permlevel == 'manager'){
		$num = 1;
		}					
  $query = "SELECT
			cluster.id,
			cluster.description,
      UNIX_TIMESTAMP(cluster.creating_time) AS creating_time,
			SUM(IF((client.manager_id = '$login'),1,0)) AS num
      FROM cluster
			LEFT JOIN client_cluster ON client_cluster.cluster_id = cluster.id
			LEFT JOIN client ON client_cluster.client_id = client.id
			WHERE 1
			AND creating_time >= '$y1-$m1-$d1'
      AND creating_time < '$y2-$m2-$d2'
			GROUP BY cluster.id
			HAVING num >= $num";
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
        <caption>Список б╕знес-центр╕в, створених за пер╕од з <b><? echo strftime("%d %B %Y р.", $dfrom);?></b> по <b><? echo strftime("%d %B %Y р.", $dto);?></b></caption>
        <tr bgcolor="#EDEDED">
          <th># п/п</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=cluster.description&amp;dir=".urlencode($dir).$url_ts?>">Б╕знес-центр</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=creating_time&amp;dir=".urlencode($dir).$url_ts?>">Дата створення</a></th>
        </tr>
<?
	$color1 ="#FFF4AB";
	$color2 = "white";
	$color = $color1;		
  for($i = 0; $i < $n; $i++){
    $cluster_id = mysql_result($result, $i, "cluster.id");
    $bc_description = htmlspecialchars(mysql_result($result, $i, "cluster.description"), ENT_QUOTES, "KOI8-R");
    $creating_time = mysql_result($result, $i, "creating_time");
		$color = ($color == $color1) ? $color2: $color1;
?>
					<tr bgcolor="<? echo $color; ?>">
            <td align="right"><? echo $i + 1;?></td>
            <td><a href="show_ll.php?cluster_id=<? echo $cluster_id;?>"><? echo "$bc_description";?></a></td>
            <td align="right"><? echo strftime("%d %B %Y", $creating_time);?></td>
          </tr>
<?
    }
	$in = $i;
?>
        </table>
      </td>
    </tr>
<?
	$num = 0;
	if($permlevel == 'manager'){
		$num = 1;
		}					
  $query = "SELECT
			cluster.id,
			cluster.description,
      UNIX_TIMESTAMP(cluster.closing_time) AS closing_time,
			SUM(IF((client.manager_id = '$login'),1,0)) AS num
      FROM cluster
			LEFT JOIN client_cluster ON client_cluster.cluster_id = cluster.id
			LEFT JOIN client ON client_cluster.client_id = client.id
			WHERE 1
			AND closing_time >= '$y1-$m1-$d1'
      AND closing_time < '$y2-$m2-$d2'
			GROUP BY cluster.id
			HAVING num >= $num";
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
        <caption>Список б╕знес-центр╕в, в╕дключених за пер╕од з <b><? echo strftime("%d %B %Y р.", $dfrom);?></b> по <b><? echo strftime("%d %B %Y р.", $dto);?></b></caption>
        <tr bgcolor="#EDEDED">
          <th># п/п</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=cluster.description&amp;dir=".urlencode($dir).$url_ts?>">Б╕знес-центр</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=closing_time&amp;dir=".urlencode($dir).$url_ts?>">Дата в╕дключення</a></th>
        </tr>
<?
	$color1 ="#FFF4AB";
	$color2 = "white";
	$color = $color1;		
  for($i = 0; $i < $n; $i++){
    $cluster_id = mysql_result($result, $i, "cluster.id");
    $bc_description = htmlspecialchars(mysql_result($result, $i, "cluster.description"), ENT_QUOTES, "KOI8-R");
    $closing_time = mysql_result($result, $i, "closing_time");
		$color = ($color == $color1) ? $color2: $color1;
?>
					<tr bgcolor="<? echo $color; ?>">
            <td align="right"><? echo $i + 1;?></td>
            <td><a href="show_ll.php?cluster_id=<? echo $cluster_id;?>"><? echo "$bc_description";?></a></td>
            <td align="right"><? echo strftime("%d %B %Y", $closing_time);?></td>
          </tr>
<?
    }
	$out = $i;
?>
        </table>
      </td>
    </tr>
		<tr>
			<td>
				<table cellspacing="1" cellpadding="2" border="0" bgcolor="silver" width="100%">
					<tr bgcolor="#EDEDED">
						<td>&nbsp;</td>
						<th>П╕дключено</th>
						<th>В╕дключено</th>
					</tr>
					<tr bgcolor="white">
						<td>Всього за пер╕од</td>
						<th><? echo $in; ?></th>
						<th><? echo $out; ?></th>
					</tr>
				</table>
			</td>
		</tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
