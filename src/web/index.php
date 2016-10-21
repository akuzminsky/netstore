<?
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
 readfile("$DOCUMENT_ROOT/head.html");
include("$DOCUMENT_ROOT/netstorecore.php");
 if(!auth($HTTP_POST_VARS["username"], $HTTP_POST_VARS["password"],$em)){ 
	loginform(isset($HTTP_POST_VARS["username"]), $em); 
	}
 else{
	 include("top.php");
  $login = $authdata['login'];
  $passwd = $authdata['passwd'];
  $permlevel = $authdata['permlevel'];
  $mysql = @mysql_connect($host, $login, $passwd); mysql_set_charset("koi8u");
  if($mysql == FALSE){
    session_timeout();
		}
	log_event($mysql, "", "", "", "", "Operator just logged in");
	}
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
