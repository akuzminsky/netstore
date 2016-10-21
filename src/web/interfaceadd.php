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
 if(empty($_POST)){
?>
 <form action="<? echo $_SERVER['PHP_SELF']; ?>" method=POST>
 <tr><td valign=top align=center>
 <table>
 <tr>
     <th>Интерфейс
 <tr>
     <td><select name=interface_id>
 <?
 	$query = "select interfaces.if_id,
		interfaces.description
		from interfaces";
	$result = mysql_query($query);
	if($result == FALSE){
		header ("Location: show_error.php?error=".mysql_error());
		mysql_close($mysql);
		exit(1);
		}
	$n = mysql_num_rows($result);
	for($i = 0; $i < $n; $i++){
		$interface_id = mysql_result($result, $i, "interfaces.if_id");
		$description = mysql_result($result, $i, "interfaces.description");
		printf("<option value='%s'>%2s | %s\n",$interface_id, $interface_id, $description);
		}
	mysql_close($mysql);
 ?>
     <td><input type='hidden' name='client_id' value=<?echo $_GET[client_id]?>>
 <tr><th><input type='submit' value='Добавить'>
 </table>
 </td></tr>
 </form>
<?
	}
 else{
 	$client_id = mysql_escape_string($_POST["client_id"]);
	$interface_id = mysql_escape_string($_POST["interface_id"]);
	
	$query = sprintf("insert into client_interface(client_id, interface_id)
		values(%s, %s)",$client_id, $interface_id);
	if(FALSE == mysql_query($query)){
		header ("Location: show_error.php?error=".mysql_error());
		mysql_close($mysql);
		exit(1);
		}
	mysql_close($mysql);
	header("Location: show_client.php?client_id=$client_id");
	exit(0);
	}
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
