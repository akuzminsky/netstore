<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  $REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
  readfile("$DOCUMENT_ROOT/head.html");
  include("$DOCUMENT_ROOT/netstorecore.php");
  session_start();
  if(!@session_is_registered('authdata')){
    header($start_url);
    }
  if($_SESSION['authdata']['permlevel'] != 'admin' 
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
    $msg = "Error: ".mysql_error()." while connecting to database ".$db;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
	if($REQUEST_METHOD == "POST"){
		mysql_query("BEGIN");
		$stwa_num = mysql_escape_string($_POST["stwa_num"]);	
		$bill_num = mysql_escape_string($_POST["bill_num"]);
		$query = "UPDATE `counter` SET `value` = '$bill_num' WHERE `variable` = 'bill_num'";
		if(mysql_query($query) == FALSE){
		  $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		  $msg = str_replace("\n", "<br>", $msg);
		  $msg = urlencode($msg);
		  header ("Location: show_error.php?error=".$msg);
		  mysql_query("ROLLBACK");
		  mysql_close($mysql);
		  exit(1);                    
		  }
		$query = "UPDATE `counter` SET `value` = '$stwa_num' WHERE `variable` = 'stwa_num'";
		if(mysql_query($query) == FALSE){
		  $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		  $msg = str_replace("\n", "<br>", $msg);
		  $msg = urlencode($msg);
		  header ("Location: show_error.php?error=".$msg);
		  mysql_query("ROLLBACK");
		  mysql_close($mysql);
		  exit(1);                    
		  }
		mysql_query("COMMIT");
		}
  $query = "SELECT * from `counter`";
  $result = mysql_query($query);
  if($result == FALSE){
      $msg = "Error: ".mysql_error()." while executing:\n".$query;
      $msg = str_replace("\n", "<br>", $msg);
      $msg = urlencode($msg);
      header ("Location: show_error.php?error=".$msg);
      mysql_close($mysql);
      exit(1);
      }
	for($i = 0; $i < mysql_num_rows($result); $i++){
		if(mysql_result($result, $i, "variable") == "bill_num"){
			$bill_num = mysql_result($result, $i, "value");
			}
		if(mysql_result($result, $i, "variable") == "stwa_num"){
			$stwa_num = mysql_result($result, $i, "value");
			}
		}
?>
  <tr>
    <td valign="top" width="20%"><? include("left_bk.php");?></td>
    <td width="80%">
      <form method="POST" action="<? echo $_SERVER["PHP_SELF"];?>" enctype="multipart/form-data">
      <table cellspacing="1" cellpadding="2" bgcolor="silver">
			<caption>Номери документ╕в</caption>
        <tr bgcolor="white">
          <td>Рахунок-фактура</td>
					<td><input type="text" name="bill_num" value="<? echo $bill_num; ?>"></td>
        </tr>
        <tr bgcolor="white">
          <td>Акт виконаних роб╕т</td>
					<td><input type="text" name="stwa_num" value="<? echo $stwa_num; ?>"></td>
        </tr>
        <tr bgcolor="white">
					<th colspan="2"><input type="submit" name="save" value="Зберегти"></th>
        </tr>
      </table>
      </form>
    </td>
  </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
