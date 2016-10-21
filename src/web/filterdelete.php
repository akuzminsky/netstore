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

	$filter_id = mysql_escape_string($_GET["filter_id"]);
	$client_id = mysql_escape_string($_GET["client_id"]);
	
	delete_filter($mysql, $filter_id, "yes");
	header("Location: show_client.php?client_id=".$client_id);
 mysql_close($mysql);
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
