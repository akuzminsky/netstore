<?
 $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
 readfile("$DOCUMENT_ROOT/head.html");
 session_start();
 if(!@session_is_registered('authdata') || $_SESSION['authdata']['permlevel'] != 'admin'){
 	header("Location: https://netstore.nbi.com.ua/");
	}
 if($_SESSION['authdata']['permlevel'] != 'admin'){
 	header("Location: https://netstore.nbi.com.ua/");
	}
 
 $authdata = $_SESSION['authdata'];
 include("$DOCUMENT_ROOT/netstorecore.php");
 include("top.php");
 $login = $authdata['login'];
 $passwd = $authdata['passwd'];
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
 $client_interface_id = mysql_escape_string($_GET["client_interface_id"]);
 $client_id = mysql_escape_string($_GET["client_id"]);
 
 $query = "delete from client_interface where id = '$client_interface_id'";
 if(FALSE == mysql_query($query)){
 	header ("Location: show_error.php?error=".mysql_error());
	mysql_close($mysql);
	exit(1);
	}
 mysql_close($mysql);
 header("Location: show_client.php?client_id=$client_id");
?>
