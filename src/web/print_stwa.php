<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  //readfile("$DOCUMENT_ROOT/head.html");
  include("$DOCUMENT_ROOT/netstorecore.php");
  session_start();
  if(!@session_is_registered('authdata')){
    header($start_url);
    }
  if($_SESSION['authdata']['permlevel'] != 'admin' 
      && $_SESSION['authdata']['permlevel'] != 'manager'
      && $_SESSION['authdata']['permlevel'] != 'topmanager'){
    header($start_url);
    }
 
  $authdata = $_SESSION['authdata'];
  //include("top.php");
  $login = $authdata['login'];
  $passwd = $authdata['passwd'];
  $permlevel = $authdata['permlevel'];
  $mysql = @mysql_connect($host, $login, $passwd); mysql_set_charset("koi8u");
  if($mysql == FALSE){
    echo "Cannot connect to mysql server";
    exit(1);
    }
  if(FALSE == mysql_select_db($db)){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $stwa_id = mysql_escape_string($_GET["stwa_id"]);
	print_stwa($mysql, $stwa_id, 2, sprintf("Statement_of_work_agreement-%08u.pdf", $stwa_id), "inline", true);
	exit(0);
?>
