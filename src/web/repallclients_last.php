<?
 $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
 readfile("$DOCUMENT_ROOT/head.html");
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
 include("top.php");
 ?>
 <tr><td valign=top>
 <?
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
	
 $query = "
 	select client.id,
		client.description,
		sum(traffic_snapshot.incoming) as incoming,
		sum(traffic_snapshot.outcoming) as outcoming
	from client
	left join traffic_snapshot on traffic_snapshot.client_id = client.id
	where traffic_snapshot.timestamp >= '2003-07-01 00:00:00'
	and traffic_snapshot.timestamp < '2003-08-01 00:00:00'
	group by client.id
	order by client.description";
 
 $result = mysql_query($query);
 if($result == FALSE){
 	header ("Location: show_error.php?error=".mysql_error());
	mysql_close($mysql);
	exit(1);
	}
	
 ?>
 <table align=center cellspacing="1" cellpadding="2" border="0" bgcolor=silver width=100%><caption>Трафик всех клиентов за прошлый месяц<? echo $last_update;?></caption>
	<tr bgcolor=white>
		<tr>
			<th bgcolor=lightgreen width=15%>&nbsp;</th>
			<th bgcolor=lightgreen width=15%>Клиент</th>
			<th bgcolor=lightgreen width=70%>
				<table width=100% cellspacing="1" cellpadding="2" border="0" bgcolor=silver>
					<tr bgcolor=lightgreen>
						<th colspan=3>Трафик</th>
					</tr>
					<tr>
						<th width=33% bgcolor=lightgreen>Общий</th>
						<th width=33% bgcolor=lightgreen>Сумма по фильтрам(украина)</th>
						<th width=33% bgcolor=lightgreen>Общий без фильтров(не-украина)</th>
					</tr>
				</table>
			</th>
		</tr>
 <?
 $factor = 1024*1024;
 $n = mysql_num_rows($result);
 for($i = 0; $i < $n; $i++){
 	$incoming = mysql_result($result, $i, "incoming");
 	$outcoming = mysql_result($result, $i, "outcoming");
	$client_id =  mysql_result($result, $i, "client.id");
	$q = "select 
			
			sum(filter_counter_snapshot.incoming + filter_counter_snapshot.outcoming) as tr 
		from filter_counter_snapshot 
		left join filter on filter_counter_snapshot.filter_id = filter.id
		where filter.client_id = $client_id
		and filter_counter_snapshot.timestamp > '2003-07-01 00:00:00' 
			and filter_counter_snapshot.timestamp < '2003-08-01 00:00:00'
			having tr is not null";
	$r = mysql_query($q);
 	if($r == FALSE){
 		header ("Location: show_error.php?error=".mysql_error());
		mysql_close($mysql);
		exit(1);
	}
	$num = mysql_num_rows($r);
	if($num != 0){
		$fiter_tr = mysql_result($r, 0, "tr");
		}
	else{
		$fiter_tr = 0;
		}
?>
		<tr bgcolor=white>
			<td valign=center align=left>&nbsp;</td>
			<th valign=center align=left><?echo mysql_result($result, $i,"client.description")?></td>
			<td>
				<table width=100% cellspacing="1" cellpadding="2" border="1" bgcolor=silver>
					<tr bgcolor=white>
						<td width=33% align=right bgcolor=ffff99><?echo number_format(($incoming + $outcoming)/$factor, 4, ".", " ")?></td>
						<td width=33% align=right><? echo number_format(($fiter_tr)/$factor, 4, ".", " ")?></td>
						<td width=33% align=right><? echo number_format(($incoming + $outcoming - $fiter_tr)/$factor, 4, ".", " ")?></td>
					</tr>
				</table>
			</td>
		</tr>
<?
	}
	?>
 </table>
 <?
 mysql_close($mysql);
 ?>
 </td></tr>
 <?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
