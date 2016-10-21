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
			&& $_SESSION['authdata']['permlevel'] != 'support'
			&& $_SESSION['authdata']['permlevel'] != 'juniorsupport'
			&& $_SESSION['authdata']['permlevel'] != 'accountoperator'
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
	$REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
	if($REQUEST_METHOD == "POST"){
		if(is_uploaded_file($_FILES['userfile']['tmp_name'])){
			copy($_FILES['userfile']['tmp_name'], $htdocs_dir."/img/".$_FILES['userfile']['name']);
			$scheme = " scheme = '".mysql_escape_string("img/".$_FILES['userfile']['name'])."', ";
			log_event($mysql, "", "cluster", $_POST["cluster_id"], "update", "Uploaded file ".$_FILES["userfile"]["name"]);
			}
		else{
			$scheme = "";
			}
		$cluster_id = mysql_escape_string($_POST["cluster_id"]);
		$description = mysql_escape_string($_POST["description"]);
		$network = mysql_escape_string($_POST["network"]);
		$gateway = mysql_escape_string($_POST["gateway"]);
		$switch = mysql_escape_string($_POST["switch"]);
		$equipment = mysql_escape_string($_POST["equipment"]);
		$d1 = mysql_escape_string($_POST["d1"]);
		$m1 = mysql_escape_string($_POST["m1"]);
		$y1 = mysql_escape_string($_POST["y1"]);
		$d2 = mysql_escape_string($_POST["d2"]);
		$m2 = mysql_escape_string($_POST["m2"]);
		$y2 = mysql_escape_string($_POST["y2"]);
		if($cluster_id != ""){
			$query = "UPDATE cluster SET 
					".$scheme."
					network = '$network',
					description = '$description',
					gateway = '$gateway',
					switch = '$switch',
					equipment = '$equipment',
					creating_time = '$y1-$m1-$d1',
					closing_time = '$y2-$m2-$d2'
					WHERE id = '$cluster_id'";
			$result = mysql_query($query);
			if($result == FALSE){
				$msg = "Error: ".mysql_error()." while executing:\n".$query;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".$msg);
				mysql_close($mysql);
				exit(1);
				}
			log_event($mysql, "", "cluster", "$cluster_id", "update", "Query:\n".$query);
			header ("Location: show_ll.php?cluster_id=".$cluster_id);
			exit(1);
			}
		else{
			$msg = "Не визначено ╕дентиф╕катор б╕знес-центру";
			header ("Location: show_error.php?error=".$msg);
			exit(1);
			}
		}
	
	$cluster_id = mysql_escape_string($_GET["cluster_id"]);
	$order = mysql_escape_string($_GET["order"]);
	$dir = mysql_escape_string($_GET["dir"]);
	if($order == ""){
		$order = $cluster_id == "all" ? "ord1, ord2, cluster.description" : "cluster.description, ord1, ord2";
		}
	if($dir == ""){
		$dir = "ASC";
		}
	$query = "SELECT
			client.id,
			client.login,
			cluster.description,
			client.description,
			client.person,
			IF(client.inactivation_time > NOW() OR client.inactivation_time IS NULL, 'yes', 'no') AS active,
			IF(client.inactivation_time > NOW() OR client.inactivation_time IS NULL, 0, 1) AS ord1,
			IF(client.blocked = 'y', 1, 0) AS ord2,
			client.blocked,
			client.phone,
			client.email,
			client.activation_time,
			client.inactivation_time,
			client.blocking_time
			FROM client
			LEFT JOIN client_cluster ON client.id = client_cluster.client_id
			LEFT JOIN cluster ON cluster.id = client_cluster.cluster_id
			WHERE 1";
	switch($cluster_id){
		case "ll":
			$query .= " AND client_cluster.cluster_id IS NULL";
			break;
		case "all" :
			break;
		default:
			$query .= " AND client_cluster.cluster_id = '$cluster_id'";
		}
	if($permlevel == "manager"){
		$query = $query." AND client.manager_id = '$login'";
		}
	$query .= " ORDER BY $order $dir";
	$result = mysql_query($query);
	if($result == FALSE){
		$msg = "Error: ".mysql_error()." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		mysql_close($mysql);
		exit(1);
		}
 log_event($mysql, "", "cluster", "$cluster_id", "view", "Get list of the clients. Cluster id: $cluster_id");
 $n = mysql_num_rows($result);
 $dir = ($dir == "ASC") ? "DESC" : "ASC";
?>
  <tr>
    <td>
      <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
        <tr bgcolor="white">
          <th># п/п</th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?cluster_id=".$cluster_id."&amp;order=cluster.description&amp;dir=$dir"?>">Б╕знес-центр</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?cluster_id=".$cluster_id."&amp;order=client.login&amp;dir=$dir"?>">Лог╕н</a></th>
          <th><a href="<? echo $_SERVER["PHP_SELF"]."?cluster_id=".$cluster_id."&amp;order=client.description&amp;dir=$dir"?>">Назва кл╕╓нта</a></th>
          <th>Контактна особа</th>
          <th>Телефон/Електронна адреса</th>
          <th>Дата п╕дключення</th>
          <th>Дата тичасового в╕дключення</th>
          <th><?if($permlevel == "admin"){?><a href="<? echo "clientadd.php?&amp;cluster_id=".$cluster_id; ?>"><img src="img/plus.gif" border="0" alt="Добавити запис"></a><?}?></th>
        </tr>
<?
	$working_clients = 0;
	for($i = 0; $i < $n; $i++){
		$client_id = mysql_result($result, $i, "client.id");
    $client_login = mysql_result($result, $i, "client.login");
    $client_description = mysql_result($result, $i, "client.description");
    $cluster_description = mysql_result($result, $i, "cluster.description");
    if(empty($cluster_description)) $cluster_description = "Вид╕лена л╕н╕я";
    $client_person= mysql_result($result, $i, "client.person");
    $client_phone = mysql_result($result, $i, "client.phone");
    $client_email = mysql_result($result, $i, "client.email");
		$client_activation_time = mysql_result($result, $i, "client.activation_time");
		$client_inactivation_time = mysql_result($result, $i, "client.inactivation_time");
		$client_blocking_time = mysql_result($result, $i, "client.blocking_time");
    $blocked = mysql_result($result, $i, "client.blocked");
    $active = mysql_result($result, $i, "active");
		$working_clients = ($active == "yes" || $blocked == "y") ? $working_clients + 1 : $working_clients;
		if($active == "no"){
			$bgcolor = "silver";
			}
		else{
			$bgcolor = $blocked == "n" ? "white" : "#16A9A0";
			}
?>
				<tr bgcolor="<? echo $bgcolor; ?>" onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='<? echo $bgcolor?>'">
          <td align="right"><? echo $i + 1; ?></td>
          <td><? echo $cluster_description?></td>
          <td><? echo $client_login?></td>
          <td><a href="<? if($permlevel == "juniorsupport") echo "reportpersonal.php?client_id=".$client_id; else echo "show_properties.php?client_id=".$client_id; ?>"><?echo $client_description;?></a></td>
          <td><? echo $client_person;?></td>
	  <td><table><tr><td><? echo $client_phone;?></td></tr><tr><td><? echo $client_email;?></td></tr></table></td>
          <td nowrap><? echo $client_activation_time;?></td>
          <td nowrap><? if($bgcolor == "silver"){ echo $client_inactivation_time;} if($bgcolor == "#16A9A0") { echo $client_blocking_time; } ?></td>
<?
    if($permlevel == 'admin'){
?>
          <td><a href="<? echo "clientdelete.php?client_id=$client_id&amp;cluster_id=".$cluster_id; ?>" onclick='return  confirmLink(this, "Видалити кл╕╓нта <?echo htmlspecialchars(mysql_escape_string($client_description), ENT_QUOTES, "KOI8-R");?>?")'><img src="img/dele.gif" border="0" alt="Видалити запис"></a></td>
        </tr>
<?
      }
    }
?>
       </table>
     </td>
  </tr>
	<tr>
		<td>
			<table cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
				<tr bgcolor="white">
					<td bgcolor="silver" width="50">&nbsp;</td>
					<td>Вибувший</td>
				</tr>
				<tr bgcolor="white">
					<td bgcolor="#16A9A0" width="50">&nbsp;</td>
					<td>Тимчасово закритий</td>
				</tr>
				<tr bgcolor="white">
					<td bgcolor="white" width="50">&nbsp;</td>
					<td>Активний</td>
				</tr>
			</table>
		</td>
	</tr>
<?
	if($cluster_id != "all" && $cluster_id != "ll"){
	$query = "SELECT *,
			UNIX_TIMESTAMP( creating_time ) AS unix_creating_time,
			UNIX_TIMESTAMP( closing_time ) AS unix_closing_time
			from cluster where id = '$cluster_id'";
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
	if($n != 0){
		$unix_creating_time = mysql_result($result, 0, "unix_creating_time");
		$unix_closing_time = mysql_result($result, 0, "unix_closing_time");
		$d1 = ($unix_creating_time == 0) ? 0 : date("j", $unix_creating_time);
		$m1 = ($unix_creating_time == 0) ? 0 : date("n", $unix_creating_time);
		$y1 = ($unix_creating_time == 0) ? 0 : date("Y", $unix_creating_time);
		
		$d2 = ($unix_closing_time == 0 ) ? 0 : date("j", $unix_closing_time);
		$m2 = ($unix_closing_time == 0 ) ? 0 : date("n", $unix_closing_time);
		$y2 = ($unix_closing_time == 0 ) ? 0 : date("Y", $unix_closing_time);
						
?>
	<tr>
    <td>
      <form method="POST" action="<? echo $_SERVER["PHP_SELF"];?>" enctype="multipart/form-data">
      <table align="center" cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
      <caption><? echo "Б╕знес-центр ".mysql_result($result, 0, "description");?></caption>
        <tr bgcolor="white">
          <th>Назва б╕знес-центра</th>
          <td><input type="text" name="description" value="<? echo htmlspecialchars(mysql_result($result, 0, "description"), ENT_QUOTES, "KOI8-R");?>"></td>
        </tr>
        <tr bgcolor="white">
          <th>Мережа б╕знес-центра</th>
          <td><input type="text" name="network" value="<? echo htmlspecialchars(mysql_result($result, 0, "network"), ENT_QUOTES, "KOI8-R");?>"></td>
        </tr>
        <tr bgcolor="white">
          <th>IP адреса модема</th>
          <td><input type="text" name="gateway" value="<? echo htmlspecialchars(mysql_result($result, 0, "gateway"), ENT_QUOTES, "KOI8-R");?>"></td>
        </tr>
        <tr bgcolor="white">
          <th>IP адреса комутатора</th>
          <td><input type="text" name="switch" value="<? echo htmlspecialchars(mysql_result($result, 0, "switch"), ENT_QUOTES, "KOI8-R");?>"></td>
        </tr>
        <tr bgcolor="white">
          <th>
<?
	if(mysql_result($result, 0, "scheme") != ""){
		echo "<a href=\"".mysql_result($result, 0, "scheme")."\" target=\"_new\">План-схема бизнес-центра</a>";
		}
	else{
		echo "План-схема б╕знес-центра";
		}
?>				</th>
          <td><input type="hidden" name="MAX_FILE_SIZE" value="1000000">
							<input name="userfile" type="file">
					</td>
				</tr>
        <tr bgcolor="white">
          <th>Дата створення б╕знес-центра</th>
          <td>
          	<select name="d1">
              <option <? if($d1 == 0){ echo "selected"; }?> value="0">-</option>
<?
  for($j = 1; $j <= 31; $j++){
?>
              <option <? if($j == $d1){ echo "selected"; }?>><? echo $j;?></option>
<?
    }
?>
          	</select>
          	<select name="m1">
              <option <? if($m1 == 0){ echo "selected"; }?> value="0">-</option>
<?
  for($j = 1; $j <= 12; $j++){
?>
              <option <? if($j == $m1){ echo "selected"; }?> value="<? echo $j;?>"><? echo strftime("%B", mktime(0, 0, 0, $j, 1, 2000));?></option>
<?
    }
?>
          	</select>
          	<select name="y1">
              <option <? if($y1 == 0){ echo "selected"; }?> value="0">-</option>
<?
  $y = ($y1 == 0) ? date("Y") : $y1;
  for($j = $y - 3; $j <= $y + 5; $j++){
?>
              <option <? if($j == $y1){ echo "selected"; }?>><? echo $j;?></option>
<?
    }
?>
            </select>
					</td>
        </tr>
        <tr bgcolor="white">
          <th>Дата закриття б╕знес-центра</th>
          <td>
						<select name="d2" <? if($working_clients >0 ) echo "disabled"; ?>>
              <option <? if($d2 == 0){ echo "selected"; }?> value="0">-</option>
<?
  for($j = 1; $j <= 31; $j++){
?>
              <option <? if($j == $d2){ echo "selected"; }?>><? echo $j;?></option>
<?
    }
?>
            </select>
						<select name="m2" <? if($working_clients >0 ) echo "disabled"; ?>>
              <option <? if($m2 == 0){ echo "selected"; }?> value="0">-</option>
<?
  for($j = 1; $j <= 12; $j++){
?>
              <option <? if($j == $m2){ echo "selected"; }?> value="<? echo $j;?>"><? echo strftime("%B", mktime(0, 0, 0, $j, 1, 2000));?></option>
<?
    }
?>
            </select>
						<select name="y2" <? if($working_clients >0 ) echo "disabled"; ?>>
              <option <? if($y2 == 0){ echo "selected"; }?> value="0">-</option>
<?
  $y = ($y1 == 0) ? date("Y") : $y1;
  for($j = $y - 3; $j <= $y + 10; $j++){
?>
              <option <? if($j == $y2){ echo "selected"; }?>><? echo $j;?></option>
<?
    }
?>
            </select>
					</td>
        </tr>
        <tr bgcolor="white">
        	<th>Нотатки(у кого обладнання, etc...)</th>
          <td><textarea name="equipment" rows="20" cols="40"><? echo htmlspecialchars(mysql_result($result, 0, "equipment"), ENT_QUOTES, "KOI8-R");?></textarea></td>
        </tr>
<?
	if($permlevel == "admin" || $permlevel == "support"){
?>
        <tr bgcolor="white">
          <th colspan="2">
						<input type="submit" name="save" value="Зберегти зм╕ни">
						<input type="hidden" name="cluster_id" value="<? echo $cluster_id;?>">
					</th>
				</tr>
<?
		}
?>
      </table>
      </form>
    </td>
  </tr>
<?
		}
		}
  mysql_close($mysql);
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
