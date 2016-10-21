<?
 $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
 readfile("$DOCUMENT_ROOT/head.html");
 include("$DOCUMENT_ROOT/netstorecore.php");
 session_start();
 if(!@session_is_registered('authdata')){
 	header($start_url);
	exit(1);
	}
 if($_SESSION['authdata']['permlevel'] != 'admin' && $_SESSION['authdata']['permlevel'] != 'manager' && $_SESSION['authdata']['permlevel'] != 'client'){
 	header($start_url);
	exit(1);
	}
 
 $authdata = $_SESSION['authdata'];
 include("top.php");
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
 if(isset($_POST[client_id])){
 	$client_id = $_POST[client_id];
	}
 else{
 	$query = "select id from client where login = '$login'";
	$result = mysql_query($query);
	if($result == FALSE){
		header ("Location: show_error.php?error=".mysql_error());
		mysql_close($mysql);
		exit(1);
		}
	$client_id = mysql_result($result, 0, "id");
	$_POST[client_id] = $client_id;
	}
 // If user has "client" privileges, check his validity
 if($_SESSION['authdata']['permlevel'] == 'client'){
 	if(!check_client_validity($client_id, $mysql)){
		header($start_url);
	exit(1);
		}
 	}

 if($_SESSION['authdata']['permlevel'] == 'client'){
 ?>
 <tr>
 	<td valign=top align=right><a href=logout.php>Выйти</a></td>
 </tr>
 <?
 	}

 $subscribe = $_POST[subscribe];
 $msg = "";
 if($subscribe == "yes"){
 	$query = "insert into maillist
		(client_id, email)
		values($_POST[client_id], '$_POST[email]')";
 	if(FALSE == mysql_query($query)){
 		header ("Location: show_error.php?error=".mysql_error());
		mysql_close($mysql);
		exit(1);
		}
	$msg = "$_POST[email] успешно подписан на рассылку Netstore";
 	}
 else{
 	$query = "delete from maillist where client_id = $_POST[client_id] and email = '$_POST[email]'";
 	if(FALSE == mysql_query($query)){
 		header ("Location: show_error.php?error=".mysql_error());
		mysql_close($mysql);
		exit(1);
		}
	if(mysql_affected_rows($mysql) == 0){
		$msg = "$_POST[email] не подписан на рассылку Netstore";
		}
	else{
		$msg = "$_POST[email] отписан от рассылки Netstore";
		}
 	}
 ?>
 <tr>
 	<td>
 		<table border=0 width=100%>
			<tr>
				<td><? echo "$msg"?></td>
			</tr>
			<tr>
				<td><a href="reportpersonal.php?client_id=<? echo $_POST[client_id]?>">Вернуться на начальную страницу</a></td>
			</tr>
		</table>
	</td>
 </tr>
 <?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
