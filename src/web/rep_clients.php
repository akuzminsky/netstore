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
		$order = mysql_escape_string($_GET["order"]);
		$dir = mysql_escape_string($_GET["dir"]);
		$cluster_id = mysql_escape_string($_GET["cluster_id"]);
    
		$y1 = mysql_escape_string($_GET["y1"]);
    $m1 = mysql_escape_string($_GET["m1"]);
    $d1 = mysql_escape_string($_GET["d1"]);
    
		$filter = mysql_escape_string($_GET["filter"]);
		}
	if($REQUEST_METHOD == "POST"){
		$order = mysql_escape_string($_POST["order"]);
		$dir = mysql_escape_string($_POST["dir"]);
		$cluster_id = mysql_escape_string($_POST["cluster_id"]);
    
    $y1 = mysql_escape_string($_POST["y1"]);
    $m1 = mysql_escape_string($_POST["m1"]);
    $d1 = mysql_escape_string($_POST["d1"]);
		
		$filter = mysql_escape_string($_POST["filter"]);
		}
  if($y1 == "") $y1 = date("Y");
  if($m1 == "") $m1 = date("m");
  if($d1 == "") $d1 = date("d");
	$dfrom = mktime(0, 0, 0, $m1, $d1, $y1);
	
	if($order == "") $order = "client.description";
	if($dir == "") $dir = "ASC";

	mysql_query("BEGIN");
	$cluster_condition = ($cluster_id == 0) ? "cluster.id IS NULL" : "cluster.id = '$cluster_id'";
	
	if($filter == "") $filter = "active";
	switch($filter){
		case "active" : 
										$filter_str = "`active` = 1 and blocked = 'n'"; 
										$bgcolor_row = "white";
										$list_what = "активних кл╕╓нт╕в";
										break;
		case "blocked" :
										$filter_str = "client.blocked = 'y'"; 
										$bgcolor_row = "#16A9A0";
										$list_what = "тимчасово закритих кл╕╓нт╕в";
										break;
		case "gone" : 
										$filter_str = "`active` = 0"; 
										$bgcolor_row = "silver";
										$list_what = "вибувших кл╕╓нт╕в";
										break;
		default : $filter_str = "1";
		}
	$query = "SELECT 
			client.id,
			client.description,
			client.login,
			UNIX_TIMESTAMP(client.activation_time) AS activation_time,
			UNIX_TIMESTAMP(client.inactivation_time) AS inactivation_time,
			UNIX_TIMESTAMP(client.blocking_time) AS blocking_time,
			client.person,
			client.phone,
			IF(client.inactivation_time > '$y1-$m1-$d1' OR client.inactivation_time IS NULL, 1, 0) AS `active`,
			client.blocked,
			IFNULL(cluster.description, 'Вид╕лена л╕н╕я') AS cluster_description
			FROM client
			LEFT JOIN client_cluster ON client.id = client_cluster.client_id
			LEFT JOIN cluster ON cluster.id = client_cluster.cluster_id
			WHERE 
			1
			AND $cluster_condition
			AND client.activation_time < '$y1-$m1-$d1'
			HAVING $filter_str";
	$query .= " ORDER BY ".$order." ".$dir;
	//echo "<pre>$query</pre>"; exit(0);
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
	$dir = ($dir == "ASC") ? "DESC" : "ASC";
?>
    <tr>
      <td>
        <form method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
        <table border="0">
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
              <input type="submit" name="ok" value="Зм╕нити дату">
            </td>
          </tr>
        </table>
        <input type="hidden" name="order" value="<? echo $order;?>">
        <input type="hidden" name="dir" value="<? echo $dir;?>">
        <input type="hidden" name="cluster_id" value="<? echo $cluster_id;?>">
        </form>
      </td>
    </tr>
    <tr>
      <td>
        <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver" width="100%">
				<caption>Список <? echo $list_what; ?> в б╕знес-центр╕ <b><? echo @mysql_result($result, 0, "cluster_description"); ?></b> на <b><? echo strftime("%d %B %Y р.", $dfrom);?></b></caption>
        	<tr bgcolor="#EDEDED">
        	  <th># п/п</th>
        	  <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.description&amp;dir=".urlencode($dir).$url_ts."&amp;cluster_id=".$cluster_id; ?>">Назва кл╕ента</a></th>
        	  <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.login&amp;dir=".urlencode($dir).$url_ts."&amp;cluster_id=".$cluster_id;?>">Лог╕н</a></th>
        	  <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=activation_time&amp;dir=".urlencode($dir).$url_ts."&amp;cluster_id=".$cluster_id;?>">Дата п╕дключення</a></th>
<?
		if($filter == "blocked"){
?>
        	  <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=blocking_time&amp;dir=".urlencode($dir).$url_ts."&amp;cluster_id=".$cluster_id;?>">Дата тимчасового в╕дключення</a></th>
<?
			}
?>
<?
		if($filter == "gone"){
?>
        	  <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=inactivation_time&amp;dir=".urlencode($dir).$url_ts."&amp;cluster_id=".$cluster_id;?>">Дата в╕дключення</a></th>
<?
			}
?>
        	  <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.person&amp;dir=".urlencode($dir).$url_ts."&amp;cluster_id=".$cluster_id;?>">Контактна особа</a></th>
        	  <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.person&amp;dir=".urlencode($dir).$url_ts."&amp;cluster_id=".$cluster_id;?>">Контактний телефон</a></th>
        	</tr>
<?
  $n = mysql_num_rows($result);
	for($i = 0; $i < $n; $i++){
		$client_id = mysql_result($result, $i, "client.id");
		$cluster_description = mysql_result($result, $i, "cluster_description");
		$client_description = mysql_result($result, $i, "client.description");
		$client_login = mysql_result($result, $i, "client.login");
		$activation_time = strftime("%d %B %Y", mysql_result($result, $i, "activation_time"));
		$inactivation_time = strftime("%d %B %Y", mysql_result($result, $i, "inactivation_time"));
		$blocking_time = strftime("%d %B %Y", mysql_result($result, $i, "blocking_time"));
		$client_person = mysql_result($result, $i, "client.person");
		$client_phone = mysql_result($result, $i, "client.phone");
		$bgcolor = mysql_result($result, $i,"client.blocked") == "y" ? "#16A9A0" : $bgcolor_row;
?>
					<tr bgcolor="<? echo $bgcolor; ?>">
            <td align="right"><? echo $i + 1;?></td>
            <td><a href="show_client.php?client_id=<? echo $client_id;?>"><? echo $client_description;?></a></td>
            <td><a href="show_client.php?client_id=<? echo $client_id;?>"><? echo $client_login;?></a></td>
            <td><? echo $activation_time;?></td>
<?
		if($filter == "blocked"){
?>
            <td><? echo $blocking_time;?></td>
<?
			}
?>
<?
		if($filter == "gone"){
?>
            <td><? echo $inactivation_time;?></td>
<?
			}
?>
            <td><? echo $client_person;?></td>
            <td><? echo $client_phone;?></td>
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
