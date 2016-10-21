<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
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
		header ("Location: show_error.php?error=".mysql_error());
    exit(1);
    }
  if(FALSE == mysql_select_db($db)){
    header ("Location: show_error.php?error=".mysql_error($mysql));
    mysql_close($mysql);
    exit(1);
    }
  $client_id = mysql_escape_string($_GET[client_id]);
  if(!empty($_POST["add"])){
    mysql_query("BEGIN");
    // GET current rate
    $rate = mysql_escape_string($_POST["rate"]);
    $query = "INSERT INTO `rate`(`date`, `rate`) VALUES(NOW(), $rate)";
    $result = mysql_query($query);
    if($result == FALSE){
      header ("Location: show_error.php?error=".mysql_error($mysql));
      mysql_query("ROLLBACK");
      mysql_close($mysql);
      exit(1);
      }
    $client_id = mysql_escape_string($_POST["client_id"]);
    log_event($mysql, "0", "rate", "", "add", "Set rate $rate");
		mysql_query("COMMIT");
		if($client_id != ""){
			$returnto = $_GET["returnto"];
			header("Location: $returnto?client_id=".$client_id);
			}
		else{
			header("Location: bookkeeping.php");
			}
    exit(0);
    }
  $query = "SELECT count(*) as num from `rate` WHERE `date` = NOW()";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error($mysql));
    mysql_close($mysql);
    exit(1);
    }
	$n = mysql_result($result, 0, "num");
	$rate_set = 0;
	if($n != 0){
		$rate_set = 1;
		}
  $query = "SELECT `rate` from `rate` ORDER BY `date` DESC LIMIT 0,1";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error($mysql));
    mysql_close($mysql);
    exit(1);
    }
  if(mysql_num_rows($result) == 1){
    $lastrate = mysql_result($result, 0, "rate");
    }
  else{
    $lastrate = 0;
    }
?>
  <tr>
		<td valign="top" width="20%"><? include("left_bk.php");?></td>
    <td width="80%">
      <form method="POST">
      <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
      <caption>Введ╕ть курс валюти на поточний пер╕од</caption>
<?
	if($rate_set == 1){
?>
				<tr>
					<td><b><small>Увага! Курс на поточний пер╕од вже встановлено.</small></b></td>
				</tr>
<?
		}
?>
        <tr bgcolor="white">
          <td align="right">
            <input type="text" name="rate" value="<? echo $lastrate; ?>">
            <input type="hidden" name="client_id" value="<? echo $client_id; ?>">
          </td>
        </tr>
        <tr bgcolor="white">
          <td align="right"><input type="submit" name="add" value="Зберегти..."></td>
        </tr>
      </table>
      </form>
    </td>
  </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
