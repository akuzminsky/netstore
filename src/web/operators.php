<?
 $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
 readfile("$DOCUMENT_ROOT/head.html");
 include("$DOCUMENT_ROOT/netstorecore.php");
 session_start();
 if(!@session_is_registered('authdata') || $_SESSION['authdata']['permlevel'] != 'admin'){
 	header($start_url);
	}
 if($_SESSION['authdata']['permlevel'] != 'admin'){
 	header($start_url);
	}
 
 $authdata = $_SESSION['authdata'];
 include("top.php");
 $login = $authdata['login'];
 $passwd = $authdata['passwd'];
 $permlevel = $authdata['permlevel'];
 $mysql = @mysql_connect($host, $login, $passwd); mysql_set_charset("koi8u");
 if($mysql == FALSE){
	session_timeout();
	}
 if(FALSE == mysql_select_db($db)){
 	header ("Location: show_error.php?error=".mysql_error());
	mysql_close($mysql);
	exit(1);
	}
 $query = "select 
 		userlevel.user,
		permlevel.level,
		userlevel.name
 		from userlevel
		inner join permlevel on permlevel.id = userlevel.level_id
		where permlevel.level <> 'client'
		order by level, userlevel.user";
 $result = mysql_query($query);
 if($result == FALSE){
 	header ("Location: show_error.php?error=".mysql_error());
	mysql_close($mysql);
	exit(1);
	}
 log_event($mysql, "0", "userlevel, permlevel", "0", "view", "Query:\n".$query);
 $n = mysql_num_rows($result);
 ?>
 <tr>
	<td valign="top" width="20%"><? include("left_admin.php"); ?></td>
 	<td valign="top">
 		<table align=center cellspacing="1" cellpadding="2" border="0" bgcolor=silver>
 			<tr bgcolor=white>
				<th>Оператор</th>
				<th>╤м'я</th>
				<th>Р╕вень доступу</th>
				<th></th>
				<td><a href=<? echo "operatoradd.php" ?>><img src=img/plus.gif border=0 alt="Добавить запись"></a></td>
			</tr>
 <?
 for($i = 0; $i < $n; $i++){
 	$operator = mysql_result($result, $i, "userlevel.user");
 	$level = mysql_result($result, $i, "permlevel.level");
 	$name = mysql_result($result, $i, "userlevel.name");
	?>
			<tr bgcolor=white>
				<td><? echo $operator;?></td>
				<td><? echo $name;?></td>
				<td><? echo $level?></td>
				<td><a href=<? echo "operatoredit.php?operator=".$operator ?>><img src=img/edit.gif border=0 alt="Редактировать запись"></a></td>
				<td><a href=<? echo "operatordelete.php?operator=".$operator ?> onclick="return  confirmLink(this, 'Удалить оператора <?echo $operator;?>?')"><img src=img/dele.gif border=0 alt="Удалить запись"></a></td>
			</tr>
	<?
 	}
	?>
		</table>
	</td>
 </tr>
 <?
 mysql_close($mysql);
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
