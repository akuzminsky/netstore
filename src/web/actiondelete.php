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
	header ("Location: show_error.php?error=".mysql_error());
	mysql_close($mysql);
	exit(1);
	}

	$action_id = mysql_escape_string($_GET["action_id"]);
	$client_id = $_GET["client_id"];
	mysql_query("BEGIN");
	$query = "DELETE FROM filter_action WHERE id = '$action_id'";
	if(FALSE == mysql_query($query)){
		$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		exit(1);
		}
	log_event($mysql, $client_id, "filter_action", $action_id, "delete", "Removed action id $action_id");
	mysql_query("COMMIT");
	mysql_close($mysql);
	header("Location: show_client.php?client_id=".$client_id);
	exit(0);
?>
