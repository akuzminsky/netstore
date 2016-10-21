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
    select
      client.id,
       client.description,
      filter.description,
      SUM(filter_counter_snapshot.incoming + filter_counter_snapshot.outcoming) as traffic,
      filter_action.limit
    from filter_action
    left join filter_counter_snapshot on filter_counter_snapshot.filter_id = filter_action.filter_id
    left join filter on filter.id = filter_action.filter_id
    left join client on filter.client_id = client.id
    where filter_action.handler = 'webreport'
		AND YEAR(filter_counter_snapshot.timestamp) = YEAR(NOW())
		AND MONTH(filter_counter_snapshot.timestamp) = MONTH(NOW())
		GROUP BY client.id";
 
 if($permlevel == 'manager'){
   $query .= " and client.manager_id = '$login'";
  }
 
 $result = mysql_query($query);
 if($result == FALSE){
   header ("Location: show_error.php?error=".mysql_error());
  mysql_close($mysql);
  exit(1);
  }

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
                        
?>
  <tr>
    <td valign=top>
      <table align=center cellspacing="1" cellpadding="2" border="0" bgcolor=silver width=100%>
      <caption>Список клиентов, превысивших установленный лимит трафика<? echo $last_update;?></caption>
        <tr>
          <th bgcolor=lightgreen width=25%>Клиент</th>
          <th bgcolor=lightgreen width=25%>Вид трафика</th>
          <th bgcolor=lightgreen width=50% >
            <table width=100% cellspacing="1" cellpadding="2" border="0" bgcolor=silver>
              <tr bgcolor=lightgreen>
                <td colspan=2>
                  <form method='post'>
                  <table align=right>
                    <tr>
                      <td>показать в</td>
                      <td>
                        <select name='factor' onchange="this.form.submit()">
                          <option value=1073741824 <? if($factor == 1073741824) echo "selected"?> >гигабайтах</option>
                          <option value=1048576 <? if($factor == 1048576) echo "selected"?>>мегабайтах</option>
                          <option value=1024 <? if($factor == 1024) echo "selected"?>>килобайтах</option>
                          <option value=1 <? if($factor == 1) echo "selected"?>>байтах</option>
                        </select>
                      </td>
                    </tr>
                  </table>
                  </form>
                </td>
              </tr>
              <tr>
                <th width=25% bgcolor=lightgreen>Установленное ограничение</th>
                <th width=25% bgcolor=lightgreen>Текущее значение</th>
              </tr>
            </table>
          </th>
        </tr>
<?
  $n = mysql_num_rows($result);
  for($i = 0; $i < $n; $i++){
    $traffic = mysql_result($result, $i, "traffic");
    $traffic_limit = mysql_result($result, $i, "filter_action.limit");
?>
        <tr bgcolor=white>
          <th valign=center align=left>
            <a href="reportpersonal.php?client_id=<? echo mysql_result($result, $i,"client.id")?>"><?echo mysql_result($result, $i,"client.description")?></a>
          </th>
          <td valign=center align=left><?echo mysql_result($result, $i, "filter.description")?></td>
          <td>
            <table width=100% cellspacing="1" cellpadding="2" border="1" bgcolor=silver>
              <tr bgcolor=white>
                <td width=50% align=right><?echo number_format(($traffic_limit)/$factor, $prec, ".", " ")?></td>
                <td width=50% align=right><?if($traffic > $traffic_limit ) echo "<font color=red>";?><?echo number_format($traffic/$factor, $prec, ".", " ")?><?if($traffic > $traffic_limit ) echo "</font>";?></td>
              </tr>
            </table>
          </td>
        </tr>
<?
    }
 mysql_close($mysql);
?>
      </table>
    </td>
  </tr>
<?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
