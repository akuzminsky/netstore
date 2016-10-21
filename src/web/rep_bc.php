<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  readfile("$DOCUMENT_ROOT/head.html");
  include("$DOCUMENT_ROOT/netstorecore.php");
  session_start();
  if(!@session_is_registered('authdata')){
    header($start_url);
    }
  $authdata = $_SESSION['authdata'];
  $login = $authdata['login'];
  $passwd = $authdata['passwd'];
  $permlevel = $authdata['permlevel'];
  if($permlevel != "admin" 
      && $permlevel != "manager" 
      && $permlevel != "topmanager"){
    header($start_url);
    }
  include("top.php");
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
	if($order == ""){
		$order = "cluster.description";
		}
	if($dir == ""){
		$dir = "ASC";
		}
	mysql_query("BEGIN");
	$query = "SELECT 
			IFNULL(cluster.id, 'll') AS cluster_id,
			IFNULL(cluster.description, 'Вид╕лена л╕н╕я') AS cluster_description,
			SUM(IF(client.inactivation_time IS NULL AND client.activation_time IS NOT NULL AND blocked = 'n' , 1, 0) ) AS active,
			SUM(IF(client.blocked = 'y', 1, 0) ) AS blocked,
			SUM(1) AS total
			FROM client
			LEFT JOIN client_cluster ON client_cluster.client_id = client.id
			LEFT JOIN cluster ON cluster.id = client_cluster.cluster_id
			GROUP BY cluster.id";
	$query .= " ORDER BY ".$order." ".$dir;
	$result = mysql_query($query);
  //echo "$query"; exit(0);
	if($result == FALSE){
    $msg = "Error: ".mysql_error()." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
		mysql_query("ROLLBACK");
    mysql_close($mysql);
    exit(1);
    }
	$dir = ($dir == "ASC") ? "DESC" : "ASC";
?>
    <tr>
      <td>
        <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver" width="100%">
				<caption>Список б╕знес-центр╕в, станом на <b><? echo strftime("%d %B %Y р.", time());?></b></caption>
        	<tr bgcolor="#EDEDED">
        	  <th># п/п</th>
        	  <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=cluster_description&amp;dir=".urlencode($dir).$url_ts?>">Назва б╕знес-центра</a></th>
        	  <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=active&amp;dir=".urlencode($dir).$url_ts?>">К╕льк╕сть активних кл╕╓нт╕в</a></th>
        	  <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=blocked&amp;dir=".urlencode($dir).$url_ts?>">К╕льк╕сть тимчасово закритих кл╕╓нт╕в</a></th>
        	  <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=total&amp;dir=".urlencode($dir).$url_ts?>">К╕льк╕сть вибувших кл╕╓нт╕в</a></th>
        	</tr>
<?
  $n = mysql_num_rows($result);
	$sum = 0;
	for($i = 0; $i < $n; $i++){
		$cluster_id = mysql_result($result, $i, "cluster_id");
		$description = mysql_result($result, $i, "cluster_description");
		$active = mysql_result($result, $i, "active");
		$blocked = mysql_result($result, $i, "blocked");
		$total = mysql_result($result, $i, "total");
		$sum_active += $active;
		$sum_blocked += $blocked;
		$sum_gone += ($total - $active);
		$bgcolor = ($active == 0) ? "#A8A8A8" : "white";
?>
					<tr bgcolor="<? echo $bgcolor; ?>">
            <td align="right"><? echo $i + 1;?></td>
		<td><a href="show_ll.php?cluster_id=<? echo $cluster_id; ?>"><? echo $description;?></a></td>
						<td align="right" nowrap><a href="rep_clients.php?cluster_id=<? echo $cluster_id; ?>&amp;filter=active"><b><? echo $active;?></b></a></td>
		<td align="right" nowrap><a href="rep_clients.php?cluster_id=<? echo $cluster_id; ?>&amp;filter=blocked"><b><? echo $blocked;?></b></a></td>
						<td align="right" nowrap><a href="rep_clients.php?cluster_id=<? echo $cluster_id; ?>&amp;filter=gone"><b><? echo $total - $active - $blocked;?></b></a></td>
          </tr>
<?
		}
?>
          <tr bgcolor="white">
            <th align="right" colspan="2">Всього</th>
            <td align="right" nowrap><b><? echo $sum_active;?></b></td>
            <td align="right" nowrap><b><? echo $sum_blocked;?></b></td>
            <td align="right" nowrap><b><? echo $sum_gone;?></b></td>
          </tr>
          <tr bgcolor="white">
					<td colspan="5"><strong>УВАГА!</strong> К╕льк╕сть тимчасово закритих кл╕╓нт╕в завжди на поточну дату</td>
          </tr>
        </table>
      </td>
    </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
