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
	$dprev = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m") - 1, 1, date("Y")));
	$dcur = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), 1, date("Y")));
  $query = "
			SELECT
				`client`.`id`,
				`client`.`description` as client_description,
				`cluster`.`description` as cluster_description,
				SUM(IF(`traffic_snapshot`.`timestamp` >= '$dcur',
						`traffic_snapshot`.`incoming`, 0)) as incoming,
				SUM(IF(`traffic_snapshot`.`timestamp` >= '$dcur', 
						`traffic_snapshot`.`outcoming`, 0)) as outcoming,
				SUM(IF(`traffic_snapshot`.`timestamp` >= '$dprev' 
						&& `traffic_snapshot`.`timestamp` < '$dcur', 
						`traffic_snapshot`.`incoming`, 0)) as incoming_prev,
				SUM(IF(`traffic_snapshot`.`timestamp` >= '$dprev' 
						&& `traffic_snapshot`.`timestamp` < '$dcur', 
						`traffic_snapshot`.`outcoming`, 0)) as outcoming_prev
				FROM `client`
				LEFT JOIN `traffic_snapshot` ON `client`.`id` = `traffic_snapshot`.`client_id`
				LEFT JOIN `client_cluster` ON `client`.`id` = `client_cluster`.`client_id`
				LEFT JOIN `cluster` ON `cluster`.`id` = `client_cluster`.`cluster_id`
				WHERE `traffic_snapshot`.`timestamp` >= '$dprev'
				and `client`.`inactivation_time` IS NULL";
  if(isset($_GET['bc_id'])){
		$bc_id = mysql_escape_string($_GET["bc_id"]);
    $query .= " AND `cluster`.`id` = $bc_id";
    }
  if($permlevel == 'manager'){
    $query .= " AND `client`.`manager_id` = '$login'";
    }
	$query .= "
				GROUP BY `client`.`id`
				ORDER BY `cluster`.`description`, `client`.`description`
				";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
//-------------------------------------
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
    $last_update = "<br>(Останн╓ поновлення: ".mysql_result($res_last_update, 0, "lastupdate").")";
    }
  if(!isset($_POST["factor"])){
    $factor = 1048576;
    }
  else{
    $factor = $_POST["factor"];
    }
  if($factor == 1 || $factor == 1024) $prec = 0;
  if($factor == 1048576 || $factor == 1073741824) $prec = 4;
  
?>
  <tr>
    <td valign="top">
      <table align="center" cellspacing="1" cellpadding="2" border="0" bgcolor="silver" width="100%">
      <caption>Траф╕к вс╕х кл╕╓нт╕в за минулий та поточний м╕сяць<? echo $last_update;?></caption>
        <tr bgcolor="white">
          <th bgcolor="#D3D3D3" width="15%">Б╕знес-центр</th>
          <th bgcolor="#D3D3D3" width="15%">Кл╕╓нт</th>
          <th bgcolor="#D3D3D3" width="70%">
            <table width="100%" cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
              <tr bgcolor="#D3D3D3">
                <th colspan="3" width="75%">Траф╕к за поточний м╕сяць</th>
                <td colspan="1" width="25%">
                  <form method='post' action="<? echo $_SERVER['PHP_SELF']; ?>">
                  <table>
                    <tr>
                      <td>показати в</td>
										</tr>
										<tr>
                      <td>
                        <select name="factor" onchange="this.form.submit()">
                          <option value="1073741824" <? if($factor == 1073741824) echo "selected"?> >г╕габайтах</option>
                          <option value="1048576" <? if($factor == 1048576) echo "selected"?>>мегабайтах</option>
                          <option value="1024" <? if($factor == 1024) echo "selected"?>>к╕лобайтах</option>
                          <option value="1" <? if($factor == 1) echo "selected"?>>байтах</option>
                        </select>
                      </td>
                    </tr>
                  </table>
                  </form>
                </td>
              </tr>
              <tr>
                <th width="25%" bgcolor="#D3D3D3">Сумарний</th>
                <th width="25%" bgcolor="#D3D3D3">Вх╕дний</th>
                <th width="25%" bgcolor="#D3D3D3">Вих╕дний</th>
                <th width="25%" bgcolor="#D3D3D3">Сумарний за минулий м╕сяць</th>
              </tr>
            </table>
          </th>
        </tr>
<?
  $cluster_sum_incoming = 0;
  $cluster_sum_outcoming = 0;
 
  $cluster_sum_incoming_prev = 0;
  $cluster_sum_outcoming_prev = 0;

  $total_sum_incoming = 0;
  $total_sum_outcoming = 0;
  $total_sum_incoming_prev = 0;
  $cluster_sum_outcoming_prev = 0;
 
  for($i = 0; $i < $n; $i++){
    $fulltraffic_incoming = mysql_result($result, $i, "incoming");
    $fulltraffic_outcoming = mysql_result($result, $i, "outcoming");
  
    $fulltraffic_incoming_prev = mysql_result($result, $i, "incoming_prev");
    $fulltraffic_outcoming_prev = mysql_result($result, $i, "outcoming_prev");
  
    $cluster_description = mysql_result($result, $i,"cluster_description");
    $cluster_sum_incoming += $fulltraffic_incoming;
    $cluster_sum_outcoming += $fulltraffic_outcoming;
    $cluster_sum_incoming_prev += $fulltraffic_incoming_prev;
    $cluster_sum_outcoming_prev += $fulltraffic_outcoming_prev;
    if($cluster_description != "Службовий"){
      $total_sum_incoming += $fulltraffic_incoming;
      $total_sum_outcoming += $fulltraffic_outcoming;
      $total_sum_incoming_prev += $fulltraffic_incoming_prev;
      $total_sum_outcoming_prev += $fulltraffic_outcoming_prev;
      }
?>
        <tr bgcolor="white">
          <td valign="top" align="left"><?echo $cluster_description?></td>
          <th valign="top" align="left"><a href="reportpersonal.php?client_id=<? echo mysql_result($result, $i,"client.id")?>&amp;dbegin=<? echo mktime(0, 0, 0, date("m"), 1, date("Y")); ?>&amp;dend=<? echo mktime(); ?>"><?echo mysql_result($result, $i,"client_description")?></a></th>
          <td>
            <table width="100%" cellspacing="1" cellpadding="2" border="1" bgcolor="silver">
              <tr bgcolor="white">
                <td width="25%" align="right" bgcolor="#ffffcc"><?echo number_format(($fulltraffic_incoming + $fulltraffic_outcoming)/$factor, $prec, ".", " ")?></td>
                <td width="25%" align="right"><?echo number_format($fulltraffic_incoming/$factor, $prec, ".", " ");?></td>
                <td width="25%" align="right"><?echo number_format($fulltraffic_outcoming/$factor, $prec, ".", " ")?></td>
                <td width="25%" align="right" bgcolor="#ffffcc"><?echo number_format(($fulltraffic_outcoming_prev + $fulltraffic_incoming_prev)/$factor, $prec, ".", " ")?></td>
              </tr>
            </table>
          </td>
        </tr>
<?
    if( (!($i + 1 < $n)) ? TRUE : $cluster_description != mysql_result($result, $i + 1, "cluster_description")){
?>
        <tr bgcolor="white">
          <th>&nbsp;</th>
          <th align="left"><i>Всього по б╕знес-центру <? echo $cluster_description?></i></th>
          <td>
            <table width="100%" cellspacing="1" cellpadding="2" border="1" bgcolor="eeeedd">
              <tr>
                <td width="25%" align="right" bgcolor="#ffffcc"><? echo number_format(($cluster_sum_incoming + $cluster_sum_outcoming)/$factor, $prec, ".", " ")?></td>
                <td width="25%" align="right"><? echo number_format($cluster_sum_incoming/$factor, $prec, ".", " ")?></td>
                <td width="25%" align="right"><? echo number_format($cluster_sum_outcoming/$factor, $prec, ".", " ")?></td>
                <td width="25%" align="right"><? echo number_format(($cluster_sum_incoming_prev + $cluster_sum_outcoming_prev)/$factor, $prec, ".", " ")?></td>
              </tr>
            </table>
          </td>
        </tr>
        <tr bgcolor="white">
          <td colspan="3">&nbsp;</td>
        </tr>
<? 
      if($i + 1 < $n){
?>
        <tr bgcolor="eeeedd">
          <th colspan="3" align="left">Б╕знес-центр <? echo mysql_result($result, $i + 1, "cluster_description")?></th>
        </tr>
<?
        }
      $cluster_sum_incoming = 0;
      $cluster_sum_outcoming = 0;
    
      $cluster_sum_incoming_prev = 0;
      $cluster_sum_outcoming_prev = 0;
      }
    }
?>
        <tr bgcolor=white>
          <th>&nbsp;</th>
          <th align=left><i>Сума по вс╕м кл╕╓нтам</i></th>
          <td>
            <table width="100%" cellspacing="1" cellpadding="2" border="1" bgcolor="eeeedd">
              <tr>
                <td width="25%" align="right" bgcolor="#ffffcc"><? echo number_format(($total_sum_incoming + $total_sum_outcoming)/$factor, $prec, ".", " ")?></td>
                <td width="25%" align="right"><? echo number_format($total_sum_incoming/$factor, $prec, ".", " ")?></td>
                <td width="25%" align="right"><? echo number_format($total_sum_outcoming/$factor, $prec, ".", " ")?></td>
                <td width="25%" align="right"><? echo number_format(($total_sum_incoming_prev + $total_sum_outcoming_prev)/$factor, $prec, ".", " ")?></td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td colspan="2" align="right"><a href="http://validator.w3.org/"><img border="0" src="img/valid-html401.png" alt="Valid HTML 4.01!" height="31" width="88"></a></td>
  </tr>
<?
  mysql_close($mysql);
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
