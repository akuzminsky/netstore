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
    header ("Location: show_error.php?error=".mysql_error());
    exit(1);
    }
  if(FALSE == mysql_select_db($db)){
    header ("Location: show_error.php?error=".mysql_error($mysql));
    mysql_close($mysql);
    exit(1);
    }
  $cluster_id = mysql_escape_string($_GET["cluster_id"]);
  $query = "select count(*) as num from client_cluster where cluster_id = '$cluster_id'";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error($mysql));
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_result($result, 0, "num");
  if($n == 0){
    $query = "delete from cluster where id = '$cluster_id'";
    if(FALSE == mysql_query($query)){
      header ("Location: show_error.php?error=".mysql_error($mysql));
      mysql_close($mysql);
      exit(1);
      }
    log_event($mysql, 0, "cluster", $cluster_id, "delete", "Removed cluster id $cluster_id");
		header("Location: show_bc.php");
		exit(0);
    }
  else{
    $msg = "Неможливо видалити б╕знес-центр, оск╕льки в ньому ще ╓ кл╕╓нти";
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
    exit(0);
		}
?>
