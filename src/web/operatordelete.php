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
	echo "Cannot connect to mysql server";
	exit(1);
	}
 if(FALSE == mysql_select_db($db)){
 	header ("Location: show_error.php?error=".mysql_error());
	mysql_close($mysql);
	exit(1);
	}
 
 $operator = mysql_escape_string($_GET["operator"]);
 $query = "DELETE FROM userlevel WHERE USER = '$operator'";
 if(FALSE == mysql_query($query)){
	header ("Location: show_error.php?error=".mysql_error());
	mysql_close($mysql);
	exit(1);
	}
 if(FALSE == mysql_select_db('mysql')){
 	header ("Location: show_error.php?error=".mysql_error());
	mysql_close($mysql);
	exit(1);
	}
 if(FALSE == mysql_query("delete from user where user = '$_GET[operator]'")){
	header ("Location: show_error.php?error=".mysql_error());
	mysql_close($mysql);
	exit(1);
	}
 if(FALSE == mysql_query("delete from db where user = '$_GET[operator]'")){
	header ("Location: show_error.php?error=".mysql_error());
	mysql_close($mysql);
	exit(1);
	}
 if(FALSE == mysql_query("flush privileges")){
	header ("Location: show_error.php?error=".mysql_error());
	mysql_close($mysql);
	exit(1);
	}
 log_event($mysql, "", "userlevel", "", "delete", "Operator $operator removed");
 mysql_close($mysql);
 header("Location: operators.php");
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
