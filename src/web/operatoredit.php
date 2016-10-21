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
 		$msg = mysql_error();
		header ("Location: show_error.php?error=".$msg);
		mysql_close($mysql);
		exit(1);
		}
	$REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
	if($REQUEST_METHOD == "GET"){
		$operator = mysql_escape_string($_GET["operator"]);
		$query = "SELECT 
				permlevel.level,
				userlevel.name,
				userlevel.phone
				FROM permlevel
				LEFT JOIN userlevel ON userlevel.level_id = permlevel.id
				WHERE userlevel.user = '$operator'";
		$result = mysql_query($query);
		if($result == FALSE){
			$msg = "Error: ".mysql_error()." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_close($mysql);
			exit(1);
			}
		$permlevel = mysql_result($result, 0, "permlevel.level");
		$name = mysql_result($result, 0, "userlevel.name");
		$phone = mysql_result($result, 0, "userlevel.phone");
		mysql_free_result($result);
		$query = "SELECT
				permlevel.level
				FROM permlevel
				WHERE permlevel.level <> 'client'";
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
		<td valign="top" align="center">
			<form action="<? echo $_SERVER["PHP_SELF"]; ?>" method="POST" name="addUserForm" onsubmit="return checkAddUser()">
			<table>
				<tr>
					<th>Оператор</th>
					<th>Р╕вень доступу</th>
				</tr>
				<tr>
					<td><input type="text" name="operator" size="30" readonly value="<?echo $operator;?>"></td>
					<td>
						<select name='permlevel'>
<?
	for($i = 0; $i < $n; $i++){
?>
							<option value="<? echo mysql_result($result, $i, "permlevel.level");?>" <? echo (mysql_result($result, $i, "permlevel.level") == $permlevel)? "selected" : "";?>><? echo mysql_result($result, $i, "permlevel.level")?></option>
<?
		}
?>
						</select>
					</td>
				</tr>
				<tr>
					<th>Повне ╕м'я</th>
					<th>Контактний телефон</th>
				</tr>
				<tr>
					<td><input type="text" name="full_name" size="30" value="<? echo htmlspecialchars($name, ENT_QUOTES, "KOI8-R");?>"></td>
					<td><input type="text" name="phone" size="30" value="<? echo htmlspecialchars($phone, ENT_QUOTES, "KOI8-R");?>"></td>
				</tr>
				<tr>
					<th>Пароль</th>
					<th>П╕дтвердження</th>
				</tr>
				<tr>
					<td><input type="password" name="pw1" size="30"></td>
					<td><input type="password" name="pw2" size="30"></td>
				</tr>
				<tr>
					<th>Пароль для додаткових прав доступу</th>
					<th>П╕дтвердження</th>
				</tr>
				<tr>
					<td><input type="password" name="superpw1" size="30"></td>
					<td><input type="password" name="superpw2" size="30"></td>
				</tr>
				<tr>
					<th colspan="2"><input type="submit" value="Зберегти зм╕ни" class="button"></th>
				</tr>
			</table>
			</form>
		</td>
	</tr>
<?
		}
	else{
		if($_POST['pw1'] != $_POST['pw2']){
			$msg = "Введен╕ парол╕ не сп╕впадають";
			header ("Location: show_error.php?error=".$msg);
			exit(1);
			}
		if($_POST["superpw1"] != $_POST["superpw2"]){
			$msg = "Введен╕ парол╕ для додаткових прав доступу не сп╕впадають";
			header ("Location: show_error.php?error=".$msg);
			exit(1);
			}
		$operator = mysql_escape_string($_POST["operator"]);
		$name = mysql_escape_string($_POST["full_name"]);
		$phone = mysql_escape_string($_POST["phone"]);
		$level = mysql_escape_string($_POST["permlevel"]);
		$pwd = mysql_escape_string($_POST["pw1"]);
		$superpwd = mysql_escape_string($_POST["superpw1"]);
		$query = "SELECT superpasswd
				FROM userlevel
				WHERE user = '$operator'";
		$result = mysql_query($query);
		if($result == FALSE){
			$msg = "Error: ".mysql_error()." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_close($mysql);
			exit(1);
			}
		$old_superpasswd = mysql_result($result, 0, "superpasswd");
		if($superpwd != ""){
			$set_superpwd = "superpasswd = PASSWORD('".$superpwd."')";
			}
		$query = "REPLACE INTO userlevel(level_id, user, name, phone)
			SELECT permlevel.id, '$operator', '$name', '$phone'
			FROM permlevel
			WHERE permlevel.level = '$level'";
		if(FALSE == mysql_query($query)){
			$msg = "Error: ".mysql_error()." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_close($mysql);
			exit(1);
			}
		//log_event($mysql, $client_id, $table, $tkey, $action_type, $details)
		log_event($mysql, "", "userlevel", "", "update", "Query:\n".$query);
		set_permissions($mysql, $operator, $level);
		if($pwd != ""){
			$query = "UPDATE mysql.user 
				SET password = PASSWORD('$pwd')
				WHERE user = '$operator'";
			if(FALSE == mysql_query($query)){
				$msg = "Error: ".mysql_error()." while executing:\n".$query;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".$msg);
				mysql_close($mysql);
				exit(1);
				}
			}
		if($superpwd != ""){
			$query = "UPDATE userlevel
				SET $set_superpwd
				WHERE user = '$operator'";
			if(FALSE == mysql_query($query)){
				$msg = "Error: ".mysql_error()." while executing:\n".$query;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".$msg);
				mysql_close($mysql);
				exit(1);
				}
			log_event($mysql, "", "userlevel", "", "update", "$operator has got new super password");
			}
		$query = "FLUSH PRIVILEGES";
		if(FALSE == mysql_query($query)){
			$msg = "Error: ".mysql_error()." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_close($mysql);
			exit(1);
			}
		mysql_close($mysql);
		header("Location: operators.php");
		}
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
