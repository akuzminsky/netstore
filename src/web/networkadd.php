<?
	$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
	readfile("$DOCUMENT_ROOT/head.html");
	session_start();
	if(!@session_is_registered('authdata') || $_SESSION['authdata']['permlevel'] != 'admin'){
		header($start_url);
		}
	if($_SESSION['authdata']['permlevel'] != 'admin'){
		header($start_url);
		}
	$authdata = $_SESSION['authdata'];
	include("$DOCUMENT_ROOT/netstorecore.php");
	include("top.php");
	$login = $authdata['login'];
	$passwd = $authdata['passwd'];
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
	if($REQUEST_METHOD == "GET"){
		$client_id = htmlspecialchars($_GET["client_id"], ENT_QUOTES, "KOI8-R");
?>
	<form action="<? echo $_SERVER["PHP_SELF"]; ?>" method="POST">
	<tr>
		<td valign="top" align="center">
			<table>
				<tr>
					<th>Мережа</th>
					<th>Маска</th>
				</tr>
				<tr>
					<td><input type="text" name="network" size="15"></td>
					<td><input type="text" name="netmask" size="15"></td>
					<td><input type="hidden" name="client_id" value="<?echo $client_id?>"></td>
				</tr>
				<tr>
					<th colspan="2"><input type="submit" value="Добавити"></th>
				</tr>
			</table>
		</td>
	</tr>
	</form>
<?
		}
	else{
 		$client_id = mysql_escape_string($_POST["client_id"]);
		$network = mysql_escape_string(ip2long($_POST["network"]));
		$netmask = mysql_escape_string(ip2long($_POST["netmask"]));
		// Check if netmask is valid
		$i = 1;
		while((($netmask >> $i) % 2) == 0){
			$i++;
			printf("%X<br>", $netmask >> $i);	
			}
		for(; $i < 32; $i++){
			if((($netmask >> $i) % 2) == 0){
				$msg = "Error: Netmask ".$_POST["netmask"]." is incorrect";
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".$msg);
				mysql_close($mysql);
				exit(1);
				}
			}
		$query = sprintf("insert into client_network(client_id, network, netmask)
				values(%s, %u, %u)",$client_id, $network, $netmask);
		if(FALSE == mysql_query($query)){
			header ("Location: show_error.php?error=".mysql_error());
			mysql_close($mysql);
			exit(1);
			}
		mysql_close($mysql);
		header("Location: show_client.php?client_id=$client_id");
		exit(0);
		}
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
