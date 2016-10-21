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
    && $_SESSION['authdata']['permlevel'] != 'topmanager'
    && $_SESSION['authdata']['permlevel'] != 'client'){
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
  if(isset($_GET[client_id])){
    $client_id = $_GET[client_id];
    }
  else{
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
    	}
		}
  // If user has "client" privileges, check his validity
  if($_SESSION['authdata']['permlevel'] == 'client'){
    if(!check_client_validity($client_id, $mysql)){
      header($start_url);
      }
    }
  
  $client_id = mysql_escape_string($client_id);
  
  $query = "select client.id,
    client.description,
    client.manager_id,
    client.person,
    client.phone,
    client.email
    from client
    where client.id = $client_id";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  if($permlevel == 'manager'){
    $readonlystatus = "readonly";
    if($login != mysql_result($result, 0, "client.manager_id")){
      header($start_url);
      }
    }
  if($_SESSION['authdata']['permlevel'] == 'client'){
?>
  <tr>
    <td valign="top" align="right"><a href="logout.php">Выйти</a></td>
  </tr>
<?
  }
?>
  <tr>
    <td valign="top" align="left">
      <table border="0" width="100%">
        <tr>
          <td width="25%" valign="top">
            <table border="0" width="100%" bgcolor="silver" cellspacing="1" cellpadding="2">
            <caption><a href="show_client.php?client_id=<? echo mysql_result($result, 0, "client.id");?>"><? echo mysql_result($result, 0, "client.description");?></a></caption>
              <tr bgcolor="white">
                <td width="40%">Контактное лицо</td>
                <td width="60%"><? echo mysql_result($result, 0, "client.person");?></td>
              </tr>
              <tr bgcolor="white">
                <td>Телефон</td>
                <td><? echo mysql_result($result, 0, "client.phone");?></td>
              </tr>
              <tr bgcolor="white">
                <td>Адрес электронной почты</td>
                <td><a href="mailto:<? echo mysql_result($result, 0, "client.email");?>"><? echo mysql_result($result, 0, "client.email");?></a></td>
              </tr>
            </table>
          </td>
        </tr>
<?
  $query = "select client_network.id,
    client_network.network,
    client_network.netmask
    from client_network
    where client_network.client_id = $client_id";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($result);
  if($n <> 0){
?>
        <tr>
          <td>
            <table border="0" width="100%" cellspacing="1" cellpadding="2" bgcolor="silver">
            <caption>сети клиента</caption>
<?
    for($i = 0; $i < $n; $i++){
      $client_network_network = mysql_result($result, $i, "client_network.network");
      $client_network_netmask = mysql_result($result, $i, "client_network.netmask");
?>
              <tr bgcolor="white">
                <td><? echo long2ip($client_network_network);?></td>
                <td><?echo long2ip($client_network_netmask);?></td>
              </tr>
<?
      }
?>
            </table>
          </td>
        </tr>
<?
    }
  $query = "select client_interface.id,
    client_interface.interface_id,
    interfaces.description
    from client_interface
    inner join interfaces on interfaces.if_id = client_interface.interface_id
    where client_interface.client_id = $client_id";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($result);
  if($n <> 0){
?>
        <tr>
          <td>
            <table border="0" width="100% "cellspacing="1" cellpadding="2" bgcolor="silver">
            <caption>интерфейсы клиента</caption>
<?
    for($i = 0; $i < $n; $i++){
      $client_interface_interface_id = mysql_result($result, $i, "client_interface.interface_id");
      $interfaces_description = mysql_result($result, $i, "interfaces.description");
?>
              <tr bgcolor="white">
                <td><?echo $client_interface_interface_id;?></td>
                <td><?echo $interfaces_description;?></td>
              </tr>
<?
        }
?>
            </table>
          </td>
        </tr>
<?
    }
?>
        <tr>
          <td>
            <table border="0" width="100%" cellspacing="1" cellpadding="2" bgcolor="silver">
            <caption>&nbsp;</caption>
              <tr bgcolor="white">
                <td align="center"><a href="order_detailed_report.php?client_id=<?echo $_GET[client_id]?>&amp;cl_login=<? echo $login?>">Форма заказа детальной статистики</a></td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
<?
  // Whole traffic
	if(isset($_GET['dbegin'])){
		$dbegin = mysql_escape_string($_GET['dbegin']);
		}
	else{
		$dbegin = mysql_escape_string($_POST['dbegin']);
		}
	if(isset($_GET['dend'])){
		$dend = mysql_escape_string($_GET['dend']);
		}
	else{
		$dend = mysql_escape_string($_POST['dend']);
		}
  $qry_whole = "select sum(traffic_snapshot.incoming) as incoming,
    sum(traffic_snapshot.outcoming) as outcoming
    from traffic_snapshot
    where traffic_snapshot.client_id = $client_id
    and unix_timestamp(traffic_snapshot.timestamp) >= $dbegin
    and unix_timestamp(traffic_snapshot.timestamp) <= $dend";
  $res_whole = mysql_query($qry_whole);
  if($res_whole == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $n_whole = mysql_num_rows($res_whole);
  if($n_whole != 0){
    $full_incoming = mysql_result($res_whole, 0, "incoming");
    $full_outcoming = mysql_result($res_whole, 0, "outcoming");
    }
  else{
    $full_incoming = 0;
    $full_outcoming = 0;
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
    <td width="75%" valign="top">
      <table border="0" width="100%">
        <tr>
          <td>
            <table border="0" width="100%" cellspacing="1" cellpadding="2">
            <caption>Трафик за период</caption>
              <tr>
                <td>
                  <table width="100%">
                    <tr>
                      <td>
                        <table align="center">
                           <tr>
                            <td align="center">с <?echo strftime("%e %B %Y г. %X (%Z)",$dbegin)?></td>
                          </tr>
                          <tr>
                             <td align="center" colspan="2">по <?echo strftime("%e %B %Y г. %X (%Z)",$dend)?></td>
                          </tr>
                        </table>
                      </td>
                      <td>
                        <form method="post" action="<? echo $_SERVER['PHP_SELF']; ?>">
                        <input type="hidden" name="client_id" value=<? echo $client_id ?>>
                        <input type="hidden" name="dbegin" value=<? echo $dbegin ?>>
                        <input type="hidden" name="dend" value=<? echo $dend ?>>
                        <table width="100%">
                          <tr>
                            <td align="right">показать в</td>
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
                  </table>
                </td>
              </tr>
              <tr>
                <td>
                  <table border="0" width="100%" cellspacing="1" cellpadding="2" bgcolor="silver">
                    <tr bgcolor="lightgreen">
                      <th colspan="3">Общий трафик</th>
                    </tr>
                    <tr bgcolor="lightgreen">
                      <th>Суммарный</th>
                      <th>Входящий</th>
                      <th>Исходящий</th>
                    </tr>
                    <tr bgcolor="white">
                      <td bgcolor="ffff99" align="right"><?echo number_format(($full_incoming + $full_outcoming)/$factor, $prec, ".", " ")?></td>
                      <td align="right"><?echo number_format($full_incoming/$factor, $prec, ".", " ")?></td>
                      <td align="right"><?echo number_format($full_outcoming/$factor, $prec, ".", " ")?></td>
                    </tr>
                  </table>
                </td>
              </tr>
<?
  $query = "SELECT filter.client_id,
    filter.description,
    SUM(filter_counter_snapshot.incoming) AS incoming,
    SUM(filter_counter_snapshot.outcoming) AS outcoming
    from filter
    left join filter_counter_snapshot on filter.id = filter_counter_snapshot.filter_id
    where UNIX_TIMESTAMP(filter_counter_snapshot.timestamp) >= $dbegin
    and UNIX_TIMESTAMP(filter_counter_snapshot.timestamp) <= $dend
    group by filter.id
    having filter.client_id = $client_id";
  //echo $query;
  $res = mysql_query($query);
  if($res == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $n_filter = mysql_num_rows($res);
  if($n_filter != 0){
?>
              <tr bgcolor="white">
                <td>
                  <table border="0" width="100%" cellspacing="1" cellpadding="2" bgcolor="silver">
<?
    for($i = 0; $i < $n_filter; $i++){
      $description = mysql_result($res, $i, "filter.description");
      $incoming = mysql_result($res, $i, "incoming");
      $outcoming = mysql_result($res, $i, "outcoming");
?>
                    <tr bgcolor="lightgreen">
                      <th colspan="3"><? echo $description?></th>
										</tr>
										<tr bgcolor="lightgreen">
                      <th>Суммарный</th>
                      <th>Входящий</th>
                      <th>Исходящий</th>
                    </tr>
                    <tr bgcolor="white">
                      <td align="right"><? echo number_format(($incoming + $outcoming)/$factor, $prec, ".", " ")?></td>
                      <td align="right"><? echo number_format($incoming/$factor, $prec, ".", " ")?></td>
                      <td align="right"><? echo number_format($outcoming/$factor, $prec, ".", " ")?></td>
                    </tr>
<?    
      }
?>
                  </table>
                </td>
              </tr>
<?
    }
?>
              <tr>
                <td align="center">
                  <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
                  <caption>Архив отчетов по месяцам</caption>
                    <tr bgcolor="white">
                      <td colspan="3" align="center"><a href="reportpersonal.php?client_id=<?echo $client_id?>">Текущий месяц</a></td>
                    </tr>
<?
  $m = date("m");
  $m--;
  for($i = 0; $i < 4 ; $i++){
    echo "
                    <tr bgcolor=\"white\">";
    for($j = 0; $j < 3 ; $j++){
      $month = strftime("%b/%Y",mktime(0,0,0,$m,1,date("Y")));
      $dbegin = mktime(0 ,0 ,0 , $m, 1, date("Y"));
      $dend = mktime(23, 59, 59, $m +1, 0, date("Y"));
      echo "
                      <td><a href=\"reportpersonalbyperiod.php?dbegin=$dbegin&amp;dend=$dend&amp;client_id=$client_id&amp;cl_login=$login\">$month</a></td>";
      $m--;
      }
    echo "
                    </tr>";
    }
  echo "
                  </table>\n";
?>
        
                </td>
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
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
