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
  $REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
  if($REQUEST_METHOD == "GET"){
    $client_id = mysql_escape_string($_GET["client_id"]);
    }
  if($REQUEST_METHOD == "POST"){
    $client_id = mysql_escape_string($_POST["client_id"]);
    if(isset($_POST["open"])){
      mysql_query("BEGIN");
      $q = "SELECT login,vpn FROM client WHERE id = '$client_id'";
      $r = mysql_query($q);
      if($r == FALSE){
        $msg = "Error: ".mysql_error()." while executing ".$q;
        header ("Location: show_error.php?error=".$msg);
        mysql_query("ROLLBACK");
        mysql_close($mysql);
        exit(1);
        }
      $n = mysql_num_rows($r);
      if($n == 1){
        $cl_login = mysql_escape_string(mysql_result($r, 0, "login"));
        $vpn = (mysql_result($r, 0, "vpn") == "y") ? true : false;
        
        if(true){
          $q = "SELECT radreply_id FROM locked_client WHERE client_id = '$client_id'";
          $r = mysql_query($q);
          if($r == FALSE){
            $msg = "Error: ".mysql_error()." while executing ".$q;
            header ("Location: show_error.php?error=".$msg);
            mysql_query("ROLLBACK");
            mysql_close($mysql);
            exit(1);
            }
          $n = mysql_num_rows($r);
          for($i = 0; $i < $n; $i++){
            $id = mysql_escape_string(mysql_result($r, $i, "radreply_id"));
            $q = "DELETE FROM radius.radreply WHERE radius.radreply.id = '$id'";
            if(mysql_query($q) == FALSE){
              $msg = "Error: ".mysql_error()." while executing ".$q;
              header ("Location: show_error.php?error=".$msg);
              mysql_query("ROLLBACK");
              mysql_close($mysql);
              exit(1);
              }
            }
          $q = "DELETE FROM locked_client WHERE client_id = '$client_id'";
          if(mysql_query($q) == FALSE){
            $msg = "Error: ".mysql_error()." while executing ".$q;
            header ("Location: show_error.php?error=".$msg);
            mysql_query("ROLLBACK");
            mysql_close($mysql);
            exit(1);
            }
          }
        if($vpn){ // $vpn == "y"
          $q = "DELETE FROM radius.usergroup WHERE radius.usergroup.UserName = '$cl_login' AND radius.usergroup.GroupName = 'InactiveVPNUser'";
          if(mysql_query($q) == FALSE){
            $msg = "Error: ".mysql_error()." while executing ".$q;
            header ("Location: show_error.php?error=".$msg);
            mysql_query("ROLLBACK");
            mysql_close($mysql);
            exit(1);
            }
          }
        $q = "UPDATE `client` SET `blocked` = 'n', `blocking_time` = NULL WHERE `id` = '$client_id'";
        if(mysql_query($q) == FALSE){
          $msg = "Error: ".mysql_error()." while executing ".$q;
          header ("Location: show_error.php?error=".$msg);
          mysql_query("ROLLBACK");
          mysql_close($mysql);
          exit(1);
          }
        } // $n == 1
      mysql_query("COMMIT");
      }
    if(isset($_POST["close"])){
      mysql_query("BEGIN");
      $q = "SELECT login,vpn FROM client WHERE id = '$client_id'";
      $r = mysql_query($q);
      if($r == FALSE){
        $msg = "Error: ".mysql_error()." while executing ".$q;
        header ("Location: show_error.php?error=".$msg);
        mysql_query("ROLLBACK");
        mysql_close($mysql);
        exit(1);
        }
      $vpn = (mysql_result($r, 0, "vpn") == "y") ? true: false;
      $cl_login = mysql_escape_string(mysql_result($r, 0, "login"));
      $nas_sample = "mainframe";
      $nas_sample = mysql_escape_string($nas_sample);
      $q = "SELECT radius.radcheck.UserName, SUM(IF(radius.radreply.id IS NULL, 0, 1)) AS poolusage FROM radius.radcheck LEFT JOIN radius.radreply ON radius.radcheck.UserName = radius.radreply.UserName WHERE radius.radcheck.UserName LIKE '$nas_sample-%'  GROUP BY UserName";
      $r = mysql_query($q);
      if($r == FALSE){
        $msg = "Error: ".mysql_error()." while executing ".$q;
        header ("Location: show_error.php?error=".$msg);
        mysql_query("ROLLBACK");
        mysql_close($mysql);
        exit(1);
        }
      $npools = mysql_num_rows($r);
      // Looking for a pool
      $i = 0;
      while($i < $npools && mysql_result($r, $i, "poolusage") >= 50){
        $i++;
        }
      if ($i == $npools){ // That meens we haven't found
        $i++;
        $nas = $nas_sample."-".$i;
        $q = "INSERT INTO radius.radcheck(UserName, Attribute, op, Value) VALUES('$nas', 'Password', '==', 'cisco')";
        if(mysql_query($q) == FALSE){
          $msg = "Error: ".mysql_error()." while executing ".$q;
          header ("Location: show_error.php?error=".$msg);
          mysql_query("ROLLBACK");
          mysql_close($mysql);
          exit(1);
          }
        }
      if($i == 0) $i++;
      // Now we should have the nas name, which we can fill with a static route.
      $nas = $nas_sample."-".$i;
      $q = "SELECT 
          INET_NTOA(network) AS network,
          INET_NTOA(netmask) AS netmask
          FROM client_network WHERE client_id = '$client_id'";
      $ri = mysql_query($q);
      if($ri == FALSE){
          $msg = "Error: ".mysql_error()." while executing ".$q;
          header ("Location: show_error.php?error=".$msg);
          mysql_query("ROLLBACK");
          mysql_close($mysql);
          exit(1);
          }
      $n = mysql_num_rows($ri);
      for($k = 0; $k < $n; $k++){
          $network = mysql_result($ri, $k, "network");
          $netmask = mysql_result($ri, $k, "netmask");
          $q = "INSERT INTO radius.radreply(UserName, Attribute, op, Value)
              VALUES('$nas', 'Cisco-AVPair', '+=', 'ip:route=$network $netmask Null 0')";
          if(mysql_query($q) == FALSE){
            $msg = "Error: ".mysql_error()." while executing ".$q;
            header ("Location: show_error.php?error=".$msg);
            mysql_query("ROLLBACK");
            mysql_close($mysql);
            exit(1);
            }
          $id = mysql_insert_id();
          $q = "INSERT INTO locked_client(client_id, radreply_id) VALUES('$client_id', '$id')";
          if(mysql_query($q) == FALSE){
            $msg = "Error: ".mysql_error()." while executing ".$q;
            header ("Location: show_error.php?error=".$msg);
            mysql_query("ROLLBACK");
            mysql_close($mysql);
            exit(1);
            }
          }
      if($vpn){
        $q = "INSERT IGNORE INTO radius.usergroup(UserName, GroupName) VALUES('$cl_login', 'InactiveVPNUser')";
        if(mysql_query($q) == FALSE){
            $msg = "Error: ".mysql_error()." while executing ".$q;
            header ("Location: show_error.php?error=".$msg);
            mysql_query("ROLLBACK");
            mysql_close($mysql);
            exit(1);
            }
        }
      $q = "UPDATE `client` SET `blocked` = 'y', `blocking_time` = NOW() WHERE `id` = '$client_id'";
      if(mysql_query($q) == FALSE){
          $msg = "Error: ".mysql_error()." while executing ".$q;
          header ("Location: show_error.php?error=".$msg);
          mysql_query("ROLLBACK");
          mysql_close($mysql);
          exit(1);
          }
      mysql_query("COMMIT");
      }
    }
  $query = "SELECT client.id,
      client.login,
      client.description,
      client.full_name,
      client.manager_id,
      client.person,
      client.phone,
      client.email,
      client.port,
      client.notes,
      client.vpn,
      IF(client.inactivation_time > NOW() OR client.inactivation_time IS NULL, 'yes', 'no') AS active,
      client.blocked,
      client.save_flows,
      client.unlimited,
      client.has_equipment,
      client.connection_speed
      FROM client
      WHERE client.id = '$client_id'";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $responsible_manager = mysql_result($result, 0, "client.manager_id");
  if($permlevel == 'manager'){
    $readonlystatus = "readonly";
    if($login != $responsible_manager){
      header($start_url);
      }
    }
  if($responsible_manager == "" || $responsible_manager == "0"){
    $responsible_manager = "Не призначено";
    }
  if(mysql_result($result, 0, "client.vpn") == "y"){
    $vpn = true;
    }
  else{
    $vpn = false;
    }
  $cl_login = mysql_result($result, 0, "client.login");
  $blocked = (mysql_result($result, 0, "client.blocked") == "y") ? true : false;
  $has_equipment = (mysql_result($result, 0, "client.has_equipment") == "yes") ? true : false;
?>
  <tr>
    <td valign="top" width="20%">
<? include("left_cl.php"); ?>
    </td>
    <td valign="top">
      <table width="100%">
              <tr>
                <th colspan="3">
                  <form method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
                  <input type="hidden" name="client_id" value="<? echo $client_id; ?>">
<?
  if($blocked){
?>
                    <input type="submit" name="open" value="Актив╕зувати кл╕╓нта" class="button_green">
<?
    }
  else{
?>
                    <input type="submit" name="close" value="Закрити кл╕╓нта"  class="button_red">
<?
    }
?>
                  </form>
                </th>
              </tr>
        <tr>
          <form action="client_edit.php?client_id=<? echo $client_id?>" method="POST" name="cleditform">
          <td>
            <table align="center">
              <tr>
                <td colspan=2>Назва кл╕╓нта</td>
                <td><input type="text" size="40" name="cl_description" <?echo $readonlystatus?> value="<? echo htmlspecialchars(mysql_result($result, 0, "client.description"), ENT_QUOTES, "KOI8-R");?>"></td>
              </tr>
              <tr>
                <td colspan=2>Повна назва кл╕╓нта</td>
                <td><input type="text" size="40" name="cl_full_name" <?echo $readonlystatus?> value="<? echo htmlspecialchars(mysql_result($result, 0, "client.full_name"), ENT_QUOTES, "KOI8-R");?>"></td>
              </tr>
              <tr>
                <td colspan="2">Контактна особа</td>
                <td><input type="text" size="40" name="cl_person" <?echo $readonlystatus?> value="<? echo htmlspecialchars(mysql_result($result, 0, "client.person"), ENT_QUOTES, "KOI8-R");?>"></td>
              </tr>
              <tr>
                <td colspan="2">Телефон</td>
                <td><input type="text" size="40" name="cl_phone" <?echo $readonlystatus?> value="<? echo htmlspecialchars(mysql_result($result, 0, "client.phone"), ENT_QUOTES, "KOI8-R");?>"></td>
              </tr>
              <tr>
                <td colspan="2">e-Mail</td>
                <td><input type="text" size="40" name="cl_email" <?echo $readonlystatus?> value="<? echo htmlspecialchars(mysql_result($result, 0, "client.email"), ENT_QUOTES, "KOI8-R");?>"></td>
              </tr>
              <tr>
                <td colspan="2">Порт на комутатор╕</td>
                <td><input type="text" size="40" name="cl_port" <?echo $readonlystatus?> value="<? echo htmlspecialchars(mysql_result($result, 0, "client.port"), ENT_QUOTES, "KOI8-R");?>"></td>
              </tr>
              <tr>
                <td colspan="2">П╕дключений до VPN</td>
                <td><input type="checkbox" name="vpn" <? if(mysql_result($result, 0, "client.vpn") == "y"){ echo "checked"; }?>></td>
              </tr>
              <tr>
                <td colspan="2">Вибувший</td>
                <td>
                  <input type="checkbox" name="gone" <? if(mysql_result($result, 0, "active") == "no"){ echo "checked"; }?>>
                </td>
              </tr>
              <tr>
                <td colspan="2">Стан роботи кл╕╓нта</td>
                <td><? if($blocked){ echo "<b><font color=\"red\">Неактивний</font></b>"; }else{ echo "Активний"; }?></td>
              </tr>
              <tr>
                <td colspan="2">Нотатки</td>
                <td><textarea name="cl_notes" rows="20" cols="40" <?echo $readonlystatus?>><? echo htmlspecialchars(mysql_result($result, 0, "client.notes"), ENT_QUOTES, "KOI8-R");?></textarea></td>
              </tr>
              <tr>
                <td colspan="2">Збер╕гати потоки</td>
                <td><input type="checkbox" name="save_flows" <? if(mysql_result($result, 0, "client.save_flows") == "yes"){ echo "checked"; }?>></td>
              </tr>
              <tr>
                <td colspan="2">Необмежений траф╕к</td>
                <td><input type="checkbox" name="unlimited" <? if(mysql_result($result, 0, "client.unlimited") == "yes"){ echo "checked"; }?>></td>
              </tr>
              <tr>
                <td colspan="2">Абонент ма╓ встановлене обладнання</td>
                <td><input type="checkbox" name="has_equipment" <? if(mysql_result($result, 0, "client.has_equipment") == "yes"){ echo "checked"; }?>></td>
              </tr>
              <tr>
              <td colspan="2">Швидк╕сть п╕дключення<small>(kbit/sec)</small></td>
                <td><input type="text" name="connection_speed" value="<? echo mysql_result($result, 0, "client.connection_speed"); ?>"></td>
              </tr>
              <tr>
                <th colspan="3"><?if($permlevel == 'admin' || $permlevel == 'support'){?><input type="submit" value="Зберегти"><?}?></th>
              </tr>
            </table>
          </td>
          <td>
            <table align="center">
<?   
 $query = "select cluster.id,cluster.description
   from cluster
  inner join client_cluster on client_cluster.cluster_id = cluster.id
  where client_cluster.client_id = $client_id";
 $result = mysql_query($query);
 if($result == FALSE){
   header ("Location: show_error.php?error=".mysql_error());
  mysql_close($mysql);
  exit(1);
  }
 $n = mysql_num_rows($result);
 if($n != 0){
    $cluster_id = mysql_result($result, 0, "cluster.id");
    $query = "select * from cluster where id = $cluster_id";
    $result = mysql_query($query);
    if($result == FALSE){
      header ("Location: show_error.php?error=".mysql_error());
      mysql_close($mysql);
      exit(1);
      }
    if(mysql_num_rows($result) == 1){
?>
              <tr>
                <td colspan="3">
                  <table align="center" cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
                  <caption><a href="show_ll.php?cluster_id=<? echo $cluster_id?>"><? echo "Бизнес-центр ".htmlspecialchars(mysql_result($result, 0, "description"), ENT_QUOTES, "KOI8-R");?></a></caption>
                    <tr bgcolor="white">
                      <th>Мережа б╕знес-центра</th>
                      <td><input type="text" name="network" <?echo $readonlystatus?> value="<? echo htmlspecialchars(mysql_result($result, 0, "network"), ENT_QUOTES, "KOI8-R");?>"></td>
                    </tr>
                    <tr bgcolor="white">
                      <th>IP адреса модема</th>
                      <td><input type="text" name="gateway" <?echo $readonlystatus?> value="<? echo htmlspecialchars(mysql_result($result, 0, "gateway"), ENT_QUOTES, "KOI8-R");?>"></td>
                    </tr>
                    <tr bgcolor="white">
                      <th>IP адреса комутатора</th>
                      <td><input type="text" name="switch" <?echo $readonlystatus?> value="<? echo htmlspecialchars(mysql_result($result, 0, "switch"), ENT_QUOTES, "KOI8-R");?>"></td>
                    </tr>
                    <tr bgcolor="white">
                      <th colspan="2">
<?  
  if(mysql_result($result, 0, "scheme") != ""){
    echo "<a href='".mysql_result($result, 0, "scheme")."' target='_new'>План-схема бизнес-центра</a>";
    }
  else{
    echo "План-схема б╕знес-центра";
    }
?>
                      </th>
                    </tr>
                    <tr bgcolor="white">
                      <th>Нотатки(у кого обладнання, etc...)</th>
                      <td><textarea name="equipment" rows="10" cols="38" <?echo $readonlystatus?>><? echo htmlspecialchars(mysql_result($result, 0, "equipment"), ENT_QUOTES, "KOI8-R");?></textarea></td>
                    </tr>
                  </table>
                </td>
              </tr>
<?
    }
  }
 $bc = ($n == 0)? "Нет": mysql_result($result, 0, "cluster.description");
 $query = "select cluster.id,cluster.description
   from cluster order by cluster.description";
 $result = mysql_query($query);
 if($result == FALSE){
   header ("Location: show_error.php?error=".mysql_error());
  mysql_close($mysql);
  exit(1);
  }
 $n = mysql_num_rows($result);

 ?>
              <tr>
                <td><label for="checkbox_withbc">Належн╕сть до б╕знес-центра</label></td>
                <td><?if($permlevel == 'admin' || $permlevel == 'support'){?><input type="checkbox" name="withbc" <?echo $readonlystatus?> id='checkbox_withbc' <? if($bc != "Нет") echo "checked";?>><?}?></td>
                <td>
                  <select name="cl_bc" <? if($permlevel == 'manager') echo "disabled";?>>
    <?
    if($bc == "Нет") echo "
                  <option value=\"0\" selected>$bc\n";
    for($i = 0;$i < $n;$i++){
      $cluster_id = mysql_result($result, $i, "cluster.id");
      $clusterdescription = mysql_result($result, $i, "cluster.description");
      $ch = ($clusterdescription == $bc) ?$ch = 'selected': '';
      echo "
                  <option value=\"$cluster_id\" $ch>$clusterdescription\n";
      }
    ?>
                  </select>
                </td>
                <td><input type="hidden" name="client_id" value="<?echo $client_id?>"></td>
              </tr>
 <?
 $query = "select userlevel.user
 from userlevel
 left join permlevel on permlevel.id = userlevel.level_id
 where permlevel.level = 'manager'
 order by userlevel.user";
 $result = mysql_query($query);
 if($result == FALSE){
   header ("Location: show_error.php?error=".mysql_error());
  mysql_close($mysql);
  exit(1);
  }
 $n = mysql_num_rows($result);
 ?>
              <tr>
                <td colspan="2">В╕дпов╕дальний менеджер
                <td>
                  <select name="resp_manager" <? if($permlevel == 'manager') echo "disabled";?>>
<?
  if($responsible_manager == "Не призначено") echo "
                  <option value=\"0\" selected>$responsible_manager\n";
  for($i = 0;$i < $n;$i++){
    $manager_id = mysql_result($result, $i, "userlevel.user");
    $ch = ($manager_id == $responsible_manager) ? 'selected': '';
    echo "
                  <option value=\"$manager_id\" $ch>$manager_id\n";
    }
?>
                  </select>
                </tr>
                <tr>
                  <th colspan="3"><?if($permlevel == 'admin'){?><a href="clientpasswd.php?client_id=<? echo $client_id?>">Зм╕на пароля</a><?}?></th>
                </tr>
              </table>
              </form>
            </td>
          </tr>
          <tr>
<?
  $dfrom = mktime(0, 0, 0, date("m"), 1, date("Y"));
  $dto = mktime();    
?>
            <td align="center"><a href="reportpersonal.php?client_id=<? echo $client_id?>&amp;dbegin=<? echo $dfrom?>&amp;dend=<? echo $dto?>">Зв╕ти про траф╕к</a></td>
          </tr>
          <tr>
            <td align="center">
              <table title="Мереж╕ кл╕╓нта" cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
              <caption>Мереж╕ кл╕╓нта</caption>
                <tr bgcolor="white">
          <th><?if($permlevel == 'admin'){?><a href="networkadd.php?client_id=<?echo $client_id?>">+</a><?}?></th>
           <th>Мережа</th>
           <th>Маска</th>
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
  for($i = 0; $i < $n; $i++){
    $client_network_id = mysql_result($result, $i, "client_network.id");
    $client_network_network = mysql_result($result, $i, "client_network.network");
    $client_network_netmask = mysql_result($result, $i, "client_network.netmask");
?>
        <tr bgcolor="white">
          <td><?if($permlevel == 'admin'){?><a href="networkdelete.php?client_id=<?echo $client_id?>&amp;network_id=<?echo $client_network_id?>">x</a><?}?></td>
          <td><?echo long2ip($client_network_network);?></td>
          <td><?echo long2ip($client_network_netmask);?></td>
        </tr>
<?
    }
  
?>

      </table>
    </td>
  </tr>
  <tr>
    <td align="center">
      <table title="Интерфейсы клиента" cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
      <caption>╤нтерфейси кл╕╓нта</caption>
        <tr bgcolor="white">
          <th><?if($permlevel == 'admin'){?><a href="interfaceadd.php?client_id=<?echo $client_id?>">+</a><?}?></th>
          <th>╤нтерфейс</th>
          <th>Опис</th>
        </tr>
<?
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
  for($i = 0; $i < $n; $i++){
    $client_interface_id = mysql_result($result, $i, "client_interface.id");
    $client_interface_interface_id = mysql_result($result, $i, "client_interface.interface_id");
    $interfaces_description = mysql_result($result, $i, "interfaces.description");
?>
        <tr bgcolor="white">
          <td><?if($permlevel == 'admin'){?><a href="interfacedelete.php?client_id=<?echo $client_id?>&client_interface_id=<?echo $client_interface_id?>">x</a><?}?></td>
          <td><?echo $client_interface_interface_id;?></td>
          <td><?echo $interfaces_description;?></td>
        </tr>
<?
    }
  
?>
      </table>
    </td>
  </tr>
  <tr>
    <td align="center">
      <table title="Ф╕льтри" cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
      <caption>Ф╕льтри</caption>
      <tr bgcolor="white">
        <th><?if($permlevel == 'admin'){?><a href="filteradd.php?client_id=<?echo $client_id?>">+</a><?}?></th>
         <th>Назва</th>
<?
  $query = "select filter.id,
      filter.description
      from filter
      where filter.client_id = $client_id";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($result);
  for($i = 0; $i < $n; $i++){
    $filter_id = mysql_result($result, $i, "filter.id");
    $filter_description = mysql_result($result, $i, "filter.description");
?>
        <tr bgcolor="white">
          <td><?if($permlevel == 'admin'){?><a href="filterdelete.php?filter_id=<?echo $filter_id?>&amp;client_id=<?echo $client_id?>">x</a><?}?></td>
          <td><a href="filteredit.php?filter_id=<?echo $filter_id?>&amp;client_id=<?echo $client_id?>"><?echo $filter_description;?></a></td>
        </tr>
<?
    }
?>
       </table>
    </td>
  </tr>
  <tr>
    <td align="center">
      <table title="NO name yet" cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
      <caption>Filter traps</caption>
        <tr bgcolor="white">
          <th><?if($permlevel == 'admin'){?><a href="addfilter_action.php?client_id=<?echo $client_id?>">+</a><?}?></th>
          <th>Ф╕льтр</th>
          <th>Л╕м╕т, (bytes)</th>
          <th>Д╕я</th>
<?
  $query = "select 
      filter.id,
      filter.description,
      filter_action.id,
      filter_action.limit,
      filter_handler.description
      from filter_action
      left join filter on filter.id = filter_action.filter_id
      left join filter_handler on filter_handler.handler = filter_action.handler
      where filter.client_id = $client_id";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($result);
  for($i = 0; $i < $n; $i++){
    $action_id = mysql_result($result, $i, "filter_action.id");
?>
        <tr bgcolor="white">
          <td><?if($permlevel == 'admin'){?><a href="actiondelete.php?action_id=<?echo $action_id?>&amp;client_id=<?echo $client_id?>">x</a><?}?></td>
          <td><?echo mysql_result($result, $i, "filter.description")?></td>
          <td><? echo mysql_result($result, $i, "filter_action.limit")?></td>
          <td><? echo mysql_result($result, $i, "filter_handler.description")?></td>
        </tr>
<?
    }
?>
      </table>
    </td>
  </tr>
  <tr>
    <td align="right"><a href="http://validator.w3.org/"><img border="0" src="img/valid-html401.png" alt="Valid HTML 4.01!" height="31" width="88"></a></td>
  </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
