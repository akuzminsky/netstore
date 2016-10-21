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
  $login = $authdata['login'];
  $passwd = $authdata['passwd'];
  $permlevel = $authdata['permlevel'];
  $mysql = @mysql_connect($host, $login, $passwd); mysql_set_charset("koi8u");
	
	header("Content-disposition: attachment; filename=register".date("Ym").".dbf");
	header("Content-Type: application/dbf");
	passthru("/usr/local/bin/make_dbf -u $login -p $passwd -h $host -d $db");
	exit(0);
?>
