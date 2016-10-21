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
  $login = $authdata["login"];
  $passwd = $authdata["passwd"];
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
  if(empty($_POST)){
    $query = "SELECT id, level
        FROM permlevel
        WHERE level <> 'client'";
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
      <form action="<? echo $_SERVER['PHP_SELF']; ?>" method="POST" name="addUserForm" onsubmit="return checkAddUser()">
      <table>
        <tr>
          <th>Оператор</th>
          <th>Р╕вень доступу</th>
        </tr>
        <tr>
          <td><input type="text" name="operator" size="30"></td>
          <td>
            <select name="level_id">
<?
      for($i = 0; $i < $n; $i++){
?>
              <option value="<? echo mysql_result($result, $i, "id");?>"><? echo mysql_result($result, $i, "level"); ?></option>
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
          <td><input type="text" name="full_name" size="30"></td>
          <td><input type="text" name="phone" size="30"></td>
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
          <th colspan="2"><input type="submit" value="Створити оператора"></th>
        </tr>
      </table>
<?
      for($i = 0; $i < $n; $i++){
?>
        <input type="hidden" name="level<? echo mysql_result($result, $i, "id");?>" value="<? echo mysql_result($result, $i, "level"); ?>">
<?
        }
?>
      </form>
    </td>
  </tr>
<?
  }
 else{
   if($_POST["pw1"] != $_POST["pw2"]){
    $msg = "Введен╕ парол╕ не сп╕впадають";
    header ("Location: show_error.php?error=".$msg);
    exit(1);  
    }
   if($_POST["superpw1"] != $_POST["superpw2"]){
    $msg = "Введен╕ парол╕ для додаткових прав доступу не сп╕впадають";
    header ("Location: show_error.php?error=".$msg);
    exit(1);  
    }
  else{
    $level_id = mysql_escape_string($_POST["level_id"]);
    $level = mysql_escape_string($_POST["level".$level_id]);
    $operator = mysql_escape_string($_POST["operator"]);
    $name = mysql_escape_string($_POST["full_name"]);
    $phone = mysql_escape_string($_POST["phone"]);
    $pwd = mysql_escape_string($_POST["pw1"]);
    $superpwd = mysql_escape_string($_POST["superpw1"]);
		mysql_query("BEGIN");
		// Проверяем, нет ли пользователя в базе mysql
		$query = "SELECT COUNT(*) FROM `mysql`.`user` WHERE `Host` = 'localhost' and `User` = '$operator'";
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
		$num = mysql_result($result, 0);
		if($num > "0"){
			$msg = "Користувач '$operator' вже ╕сну╓ в баз╕\nВибер╕ть ╕нше ╕м'я";
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
      mysql_close($mysql);
      exit(1);
			}	
    $query = "INSERT INTO userlevel(level_id, user, superpasswd, name, phone)
        VALUES('$level_id', '$operator', PASSWORD('$superpwd'), '$name', '$phone')";
    if(FALSE == mysql_query($query)){
      $msg = "Error: ".mysql_error()." while executing:\n".$query;
      $msg = str_replace("\n", "<br>", $msg);
      $msg = urlencode($msg);
      header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
      mysql_close($mysql);
      exit(1);
      }
     if(FALSE == mysql_select_db('mysql')){
      $msg = mysql_error();
      $msg = str_replace("\n", "<br>", $msg);
      $msg = urlencode($msg);
      header ("Location: show_error.php?error=".$msg);
      mysql_close($mysql);
      exit(1);
      }
    $query = "INSERT INTO `user`(`Host`, `User`, `Password`)
      VALUES(
      'localhost',
      '$operator',
      PASSWORD('$pwd'))";
    if(FALSE == mysql_query($query)){
      $msg = "Error: ".mysql_error()." while executing:\n".$query;
      $msg = str_replace("\n", "<br>", $msg);
      $msg = urlencode($msg);
      header ("Location: show_error.php?error=".$msg);
      mysql_close($mysql);
      exit(1);
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
		set_permissions($mysql, $operator, $level);
		//log_event($mysql, $client_id, $table, $tkey, $action_type, $details)
		log_event($mysql, "", "", "", "add", "New operator:\nLogin: $operator\nLevel: $level\nName: $name\nhas been created.");
		mysql_query("COMMIT");
    mysql_close($mysql);
    header("Location: operators.php");
    }
  }
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
