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
  $query = "
      select cluster.description, 
      client.description,
      client.id,
      client.manager_id,
      traffic_cur.incoming as incoming,
      traffic_cur.outcoming as outcoming
      from client
      left join traffic_cur on traffic_cur.client_id = client.id 
      left join client_cluster on client.id = client_cluster.client_id 
      left join cluster on cluster.id = client_cluster.cluster_id
      where 1";
  if(isset($_GET['bc_id'])){
    $query .= " and cluster.id = $_GET[bc_id]";
    }
  if($permlevel == 'manager'){
    $query .= " and client.manager_id = '$login'";
    }
  $query .= " order by cluster.description, client.description";
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
  if(!isset($_POST[factor])){
    $factor = 1048576;
    }
  else{
    $factor = $_POST[factor];
    }
  if($factor == 1 || $factor == 1024) $prec = 0;
  if($factor == 1048576 || $factor == 1073741824) $prec = 4;
	$dfrom = mktime(0, 0, 0, date("m"), 1, date("Y"));
	$dto = mktime();
?>
  <tr>
    <td valign=top>
      <table align="center" cellspacing="1" cellpadding="2" border="0" bgcolor="silver" width="100%">
      <caption>Трафик всех клиентов за текущий месяц<? echo $last_update;?></caption>
        <tr bgcolor="white">
          <th bgcolor="lightgreen" width="25%">Бизнес-центр</th>
          <th bgcolor="lightgreen" width="25%">Клиент</th>
          <th bgcolor="lightgreen" width="50%">
            <table width="100%" cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
              <tr bgcolor="lightgreen">
                <th colspan="2">Трафик</th>
                <td colspan="2">
                  <form method="post" action="<? echo $_SERVER['PHP_SELF']; ?>">
                  <table>
                    <tr>
                      <td>показать в</td>
                      <td>
                        <select name="factor" onchange="this.form.submit()">
                          <option value="1073741824" <? if($factor == 1073741824) echo "selected"?> >гигабайтах</option>
                          <option value="1048576" <? if($factor == 1048576) echo "selected"?>>мегабайтах</option>
                          <option value="1024" <? if($factor == 1024) echo "selected"?>>килобайтах</option>
                          <option value="1" <? if($factor == 1) echo "selected"?>>байтах</option>
                        </select>
                      </td>
                    </tr>
                  </table>
                  </form>
                </td>
              </tr>
              <tr>
                <th width="25%" bgcolor="lightgreen"></th>
                <th width="25%" bgcolor="lightgreen">Всего</th>
                <th width="25%" bgcolor="lightgreen">Входящий</th>
                <th width="25%" bgcolor="lightgreen">Исходящий</th>
              </tr>
            </table>
          </th>
        </tr>
<?
  $cluster_sum_incoming = 0;
  $cluster_sum_outcoming = 0;
  for($i = 0; $i < $n; $i++){
    $fulltraffic_incoming = mysql_result($result, $i, "incoming");
    $fulltraffic_outcoming = mysql_result($result, $i, "outcoming"); 
    
    $cluster_description = mysql_result($result, $i,"cluster.description");
    if(mysql_result($result, $i,"client.description") == "UTC"){
      $fulltraffic_incoming = 0;
      $fulltraffic_outcoming = 0;
      }
    if(mysql_result($result, $i,"client.description") == "WNET"){
      $fulltraffic_incoming = 0;
      $fulltraffic_outcoming = 0;
      }
    $cluster_sum_incoming += $fulltraffic_incoming;
    $cluster_sum_outcoming += $fulltraffic_outcoming;
?>
        <tr bgcolor="white">
          <td valign="top" align="left"><?echo $cluster_description?></td>
          <th valign="top" align="left"><a href="reportpersonal.php?client_id=<? echo mysql_result($result, $i,"client.id")?>&amp;dbegin=<? echo $dfrom?>&amp;dend=<? echo $dto?>"><?echo mysql_result($result, $i,"client.description")?></a></th>
          <td>
            <table width="100%" cellspacing="1" cellpadding="2" border="1" bgcolor="silver">
              <tr bgcolor="white">
                <th width="25%">Общий</th>
                <td width="25%" align="right" bgcolor="ffff99"><?echo number_format(($fulltraffic_incoming + $fulltraffic_outcoming)/$factor, $prec, ".", " ")?></td>
                <td width="25%" align="right"><?echo number_format($fulltraffic_incoming/$factor, $prec, ".", " ");?></td>
                <td width="25%" align="right"><?echo number_format($fulltraffic_outcoming/$factor, $prec, ".", " ")?></td>
              </tr>
            </table>
          </td>
        </tr>
<?
    if( (!($i + 1 < $n)) ? TRUE : $cluster_description != mysql_result($result, $i + 1, "cluster.description")){
?>
        <tr bgcolor="white">
          <th>&nbsp;</th>
          <th align="left"><i>Всего по <? echo $cluster_description?></i></th>
          <td>
            <table width="100%" cellspacing="1" cellpadding="2" border="1" bgcolor="eeeedd">
              <tr>
                <td width="25%">Общий</td>
                <td width="25%" align="right" bgcolor="ffff99"><? echo number_format(($cluster_sum_incoming + $cluster_sum_outcoming)/$factor, $prec, ".", " ")?></td>
                <td width="25%" align="right"><? echo number_format($cluster_sum_incoming/$factor, $prec, ".", " ")?></td>
                <td width="25%" align="right"><? echo number_format($cluster_sum_outcoming/$factor, $prec, ".", " ")?></td>
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
          <th colspan="3" align="left">Бизнес-центр <? echo mysql_result($result, $i + 1, "cluster.description")?></th>
        </tr>
<?
        }
      $cluster_sum_incoming = 0;
      $cluster_sum_outcoming = 0;
      }
    }
?>
      </table>
<?
  mysql_close($mysql);
?>
    </td>
  </tr>
  <tr>
    <td colspan="2" align="right"><a href="http://validator.w3.org/"><img border="0" src="img/valid-html401.png" alt="Valid HTML 4.01!" height="31" width="88"></a></td>
  </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
