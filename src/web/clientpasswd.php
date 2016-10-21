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
    $client_id = mysql_escape_string($_GET["client_id"]);
		$query = "SELECT login FROM client WHERE id = $client_id";
		$result = mysql_query($query);
		if($result == FALSE){
    	$msg = "Error: ".mysql_error()." while executing:\n".$query;
    	$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
    	header ("Location: show_error.php?error=".$msg);
			mysql_close($mysql);
    	exit(1);
			}
		if(mysql_num_rows($result) == 1){
			$cl_login = mysql_result($result, 0, "login");
			}
		else{
			$msg = urlencode("No such client id ".$client_id);
			header ("Location: show_error.php?error=".$msg);
			exit(1);
			}
		mysql_free_result($result);
?>
	<form action="<? echo $_SERVER["PHP_SELF"]; ?>" method="post" name="addUserForm" onsubmit="return checkAddUser()">
	<tr>
		<td valign="top" align="center">
			<table>
				<tr>
					<th>Лог╕н</th>
					<td><? echo $cl_login; ?>
						<input type="hidden" name="cl_login" value="<?echo $cl_login; ?>">
						<input type="hidden" name="client_id" value="<?echo $client_id; ?>">
					</td>
				</tr>
				<tr>
					<th>Пароль</th>
					<td><input type="password" name="pw1" size="30"></td>
				</tr>
				<tr>
					<th>П╕дтвердження пароля</th>
					<td><input type="password" name="pw2" size="30"></td>
				</tr>
				<tr>
					<th colspan="2">
						<input type="submit" value="Зм╕нити пароль" class="button">
					</th>
				</tr>
			</table>
		</td>
	</tr>
	</form>
<?
 		readfile("$DOCUMENT_ROOT/bottom.html");
		exit(0);
		}
  if($REQUEST_METHOD == "POST"){
    $client_id = mysql_escape_string($_POST["client_id"]);
		$pw1 = mysql_escape_string($_POST["pw1"]);
		$pw2 = mysql_escape_string($_POST["pw2"]);
		$cl_login = mysql_escape_string($_POST["cl_login"]);
		if($pw1 != $pw2){
			$msg = urlencode("Парол╕ не сп╕впадають");
			header ("Location: show_error.php?error=".$msg);
			exit(1);
			}
 		if(FALSE == mysql_select_db('mysql')){
    	$msg = "Error: ".mysql_error();
    	$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
    	header ("Location: show_error.php?error=".$msg);
			mysql_close($mysql);
    	exit(1);
			}
		if($pw1 != ""){
			$query = "SET PASSWORD FOR '$cl_login'@'localhost' = PASSWORD('$pw1')";
			if(FALSE == mysql_query($query)){
    		$msg = "Error: ".mysql_error()." while executing:\n".$query;
    		$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
    		header ("Location: show_error.php?error=".$msg);
				mysql_close($mysql);
    		exit(1);
				}
			}
		log_event($mysql, $client_id, "mysql.user", $client_id, "update", "Change password for client login $cl_login, id $client_id");
		mysql_close($mysql);
		header("Location: "."show_client.php?client_id=$client_id");
		exit(0);
		}
?>
