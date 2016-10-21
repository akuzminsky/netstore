<?
 $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
 readfile("$DOCUMENT_ROOT/head.html");
 include("$DOCUMENT_ROOT/netstorecore.php");
 session_start();
 if(!@session_is_registered('authdata')){
 	header($start_url);
	}
 if($_SESSION['authdata']['permlevel'] != 'admin'
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
 $query = "select
 		cluster.id,
		cluster.description,
		SUM(traffic_cur.incoming) as incoming,
		SUM(traffic_cur.outcoming) as outcoming
	from cluster
	left join client_cluster on cluster.id = client_cluster.cluster_id
	left join traffic_cur on client_cluster.client_id = traffic_cur.client_id
	group by cluster.id
	order by cluster.description";
 $result = mysql_query($query);
 if($result == FALSE){
 	header ("Location: show_error.php?error=".mysql_error());
	mysql_close($mysql);
	exit(1);
	}
 $n = mysql_num_rows($result);

 $qry_last_update = "select max(timestamp) as lastupdate from feeding";
 $res_last_update = mysql_query($qry_last_update);
 if($res_last_update == FALSE){
 	header ("Location: show_error.php?error=".mysql_error());
	mysql_close($mysql);
	exit(1);
	}
  $n_last_update = mysql_num_rows($res_last_update);
  if($n_last_update == 1){
  	$last_update = "<br>(Последнее обновление: ".mysql_result($res_last_update, 0, "lastupdate").")";
	}
 ?>
 <table align=center cellspacing="1" cellpadding="2" border="0" bgcolor=silver><caption>Трафик всех бизнес-центров за текущий месяц без учета фильтров<? echo $last_update;?></caption>
	<tr bgcolor=white>
		<tr>
			<th bgcolor=lightgreen width=33%>Бизнес-центр</th>
			<th bgcolor=lightgreen width=67%>
				<table width=100% cellspacing="1" cellpadding="2" border="0" bgcolor=silver>
					<tr>
						<th colspan=4 bgcolor=lightgreen>Трафик</td>
					</tr>
					<tr>
						<th width=33% bgcolor=lightgreen>Всего</th>
						<th width=33% bgcolor=lightgreen>Входящий</th>
						<th width=33% bgcolor=lightgreen>Исходящий</th>
					</tr>
				</table>
			</th>
		</tr>
 <?
 $sum_incoming = 0;
 $sum_outcoming = 0;
 for($i = 0; $i < $n; $i++){
 	$fulltraffic_incoming = mysql_result($result, $i,"incoming");
 	$fulltraffic_outcoming = mysql_result($result, $i,"outcoming");
	$sum_incoming += $fulltraffic_incoming;
	$sum_outcoming += $fulltraffic_outcoming;
 	$bc_id = mysql_result($result, $i, "cluster.id");
 	?>
		<tr bgcolor=white>
			<th valign=center align=left><a href="repallclientscur.php?bc_id=<? echo $bc_id?>"><?echo (mysql_result($result, $i,"cluster.description") == "") ? "Выделенка": mysql_result($result, $i,"cluster.description")?></a></td>
			<td>
				<table width=100% cellspacing="1" cellpadding="2" border="1" bgcolor=silver>
					<tr bgcolor=white>
						<td width=33% align=right bgcolor=ffff99><?echo number_format($fulltraffic_incoming + $fulltraffic_outcoming, 0, ".", " ")?></td>
						<td width=33% align=right><?echo number_format($fulltraffic_incoming, 0, ".", " ");?></td>
						<td width=33% align=right><?echo number_format($fulltraffic_outcoming, 0, ".", " ")?></td>
					</tr>
				</table>
			</td>
		</tr>
	<?
	}
 	?>
		<tr bgcolor=eeeedd>
			<th valign=center align=left>Всего</th>
			<td>
				<table width=100% cellspacing="1" cellpadding="2" border="1" bgcolor=silver>
					<tr bgcolor=white>
						<td width=33% align=right bgcolor=ffff99><? echo number_format($sum_incoming + $sum_outcoming, 0, ".", " ")?></td>
						<td width=33% align=right><? echo number_format($sum_incoming, 0, ".", " ")?></td>
						<td width=33% align=right><? echo number_format($sum_outcoming, 0, ".", " ")?></td>
					</tr>
				</table>
			</td>
		</tr>
 </table>
 <?
 mysql_close($mysql);
 ?>
 </td></tr>
 <?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
