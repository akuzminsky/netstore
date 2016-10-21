<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  readfile("$DOCUMENT_ROOT/head.html");
  session_start();
  if(!@session_is_registered('authdata')){
    header($start_url);
    }
  if($_SESSION['authdata']['permlevel'] != 'admin'
        && $_SESSION['authdata']['permlevel'] != 'topmanager'){
    header($start_url);
    }
  $authdata = $_SESSION['authdata'];
  include("$DOCUMENT_ROOT/netstorecore.php");
  include("$DOCUMENT_ROOT/top.php");
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
	$type = mysql_escape_string($_GET["type"]);
	if($type != "other"){
		$type = "ordinary";
		}
?>
  <tr>
    <td align="left" valign="top" nowrap><?include("left_admin.php");?></td>
    <td>&nbsp;</td>
  </tr>
<?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
