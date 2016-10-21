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
 				&& $_SESSION['authdata']['permlevel'] != 'support'
 				&& $_SESSION['authdata']['permlevel'] != 'juniorsupport'
 				&& $_SESSION['authdata']['permlevel'] != 'accountoperator'
				&& $_SESSION['authdata']['permlevel'] != 'topmanager'){
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
 $num = 0;
 if($permlevel == 'manager'){
 	$num = 1;
	}
 $query = "select cluster.id,
	cluster.description, 
	sum(if((client.manager_id = '$login'),1,0)) as num
	from cluster
	left join client_cluster on client_cluster.cluster_id = cluster.id
	left join client on client_cluster.client_id = client.id
	group by cluster.id
	having num >= $num
	order by cluster.description";
 $result = mysql_query($query);
 if($result == FALSE){
 	header ("Location: show_error.php?error=".mysql_error());
	mysql_close($mysql);
	exit(1);
	}
 log_event($mysql, "", "cluster,client_cluster", "", "view", "Get list of Business Centers");
 $n = mysql_num_rows($result);
 ?>
 <tr><td valign=top>
 <table align=center>
 	<tr>
	<th>Бизнес-центр
	<th><?if($permlevel == 'admin'){?><a href=<? echo "bcadd.php" ?>><img src=img/plus.gif border=0 alt="Добавить запись"></a><?}?>
	</tr>
 <?
 for($i = 0; $i < $n; $i++){
 	$cluster_id = mysql_result($result, $i, "cluster.id");
 	$cluster_description = mysql_result($result, $i, "cluster.description");
	?>
	<tr>
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="<? echo "show_ll.php?cluster_id=".$cluster_id; ?>"><?echo htmlentities($cluster_description, ENT_QUOTES, "KOI8-R");?></a></td>
		<?
		if($permlevel == 'admin'){
		?>
			<td><a href="<? echo "bcdelete.php?cluster_id=".$cluster_id ?>" onclick="return  confirmLink(this, 'Удалить бизнес-центр <?echo $cluster_description;?>?')"><img src="img/dele.gif" border="0" alt="Удалить запись"></a></td>
			<?}
		?>
	</tr>
	<?
 	}
 mysql_close($mysql);
 ?>
 </table>
 </td></tr>
 <?
 
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
