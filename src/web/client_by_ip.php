<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  include("$DOCUMENT_ROOT/netstorecore.php");
  readfile("$DOCUMENT_ROOT/head.html");
	session_start();
  if(!@session_is_registered('authdata')){
    header($start_url);
    }
  if($_SESSION['authdata']['permlevel'] != 'admin' 
      && $_SESSION['authdata']['permlevel'] != 'topmanager'
			&& $_SESSION['authdata']['permlevel'] != 'support'
			){
    header($start_url);
    }
 
  $authdata = $_SESSION['authdata'];
  $login = $authdata['login'];
  $passwd = $authdata['passwd'];
  $permlevel = $authdata['permlevel'];
  $mysql = @mysql_connect($host, $login, $passwd); mysql_set_charset("koi8u");
  if($mysql == FALSE){
    session_timeout();
		}
  if(FALSE == mysql_select_db($db)){
    $msg = "Error: ".mysql_error()." while connecting to database ".$db;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
	include("top.php");
  $REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
  if($REQUEST_METHOD == "GET"){
?>
  <tr>
    <td valign="top" width="20%"><? include("left_rp.php");?></td>
    <td width="80%" valign="top">
			<form name="register_by_period" method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
			<table border="0">
			<caption>Пошук кл╕╓нта за IP-адресою</caption>
				<tr>
					<td>IP-адреса</d>
					<td><input type="text" size="16" name="ip"></td>
				</tr>
				<tr>
					<th colspan="2"><input type="submit" name="find" value="Пошук"></th>
				</tr>
			</table>
			</form>
		</td>
	</tr>

<?
  	readfile("$DOCUMENT_ROOT/bottom.html");
		exit(0);
		}
	if($REQUEST_METHOD == "POST"){
		$ip = mysql_escape_string($_POST["ip"]);
		// Starting transaction
    if(mysql_query("BEGIN") == FALSE){
			$msg = "Error: ".mysql_error()." while starting transaction and executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
      mysql_close($mysql);
      exit(1);
      }
		// Получаем трафик клиентов
		$query = "SELECT
			client.id,
			cluster.id,
			client.description,
			client.full_name,
			cluster.id,
			cluster.description
			FROM client_network
			LEFT JOIN client ON client_network.client_id = client.id
			LEFT JOIN client_cluster ON client_cluster.client_id = client.id
			LEFT JOIN cluster ON client_cluster.cluster_id = cluster.id
			WHERE
			INET_ATON( '$ip' ) & client_network.netmask = client_network.network";
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
?>
	<tr>
		<td valign="top" width="20%"><? include("left_rp.php");?></td>
		<td width="80%" valign="top">
			<table width="100%" cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
			<caption>Результати пошуку</caption>
				<tr bgcolor="lightgrey">
					<th># п/п</th>
					<th>Б╕знес-центр</th>
					<th>Абонент</th>
				</tr>
<?
		$color1 ="#FFF4AB";
		$color2 = "white";
		$color = $color1;
    for($i = 0; $i < mysql_num_rows($result); $i++){
			$description = mysql_result($result, $i, "client.description");
			$client_id = mysql_result($result, $i, "client.id");
			$cluster_id = mysql_result($result, $i, "cluster.id");
			$cl_description = mysql_result($result, $i, "cluster.description");
			$cl_description = ($cl_description == "") ? "Вид╕лена л╕н╕я": $cl_description;
			$full_name = mysql_result($result, $i, "client.full_name");
			$cluster_id = ($cluster_id == "") ? "ll" : $cluster_id;
			if($full_name == ""){
				$full_name = $description;
				}
			$color = ($color == $color1) ? $color2: $color1;	
?>
				<tr bgcolor="<? echo $color; ?>">
					<td><? echo $i + 1; ?></td>
					<td align="left"><a href="show_ll.php?cluster_id=<? echo $cluster_id; ?>"><? echo $cl_description; ?></a></td>
					<th align="left"><a href="show_client.php?client_id=<? echo $client_id; ?>"><? echo $full_name; ?></a></th>
				</tr>
<?
			}
?>
			</table>
		</td>
	</tr>
<?
		log_event($mysql, "", "client", "", "view", "Found clients with IPv4 $ip");
		mysql_query("COMMIT");
		exit(0);
		}
	readfile("$DOCUMENT_ROOT/bottom.html");
?>
