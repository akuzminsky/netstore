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
    && $_SESSION['authdata']['permlevel'] != 'client'
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
  $REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
  if($REQUEST_METHOD == "GET"){
    if(isset($_GET["client_id"])){
      $client_id = mysql_escape_string($_GET["client_id"]);
      }
    $dbegin = mysql_escape_string($_GET["dbegin"]);
    $dend = mysql_escape_string($_GET["dend"]);
    if(isset($_GET["factor"])){
      $factor = mysql_escape_string($_GET["factor"]);
      }
    else{
      $factor = 1048576;
      }
    }
  //echo "REQUEST_METHOD $REQUEST_METHOD";
  //exit(0);
  if($REQUEST_METHOD == "POST"){
    if(isset($_POST["client_id"])){
      $client_id = mysql_escape_string($_POST["client_id"]);
      }
    $dbegin = mysql_escape_string($_POST["dbegin"]);
    $dend = mysql_escape_string($_POST["dend"]);
    //echo "dbegin $dbegin dend $dend";
    //exit(0);
    if(isset($_POST["factor"])){
      $factor = mysql_escape_string($_POST["factor"]);
      }
    else{
      $factor = 1048576;
      }
    }
  if($dbegin == "" || $dend == ""){
    $dbegin = mktime(0, 0, 0, date("m"), 1, date("Y"));
    $dend = mktime();
    }

  if(!isset($client_id)){
    $query = "select id from client where login = '$login'";
    $result = mysql_query($query);
    if($result == FALSE){
      header ("Location: show_error.php?error=".mysql_error());
      mysql_close($mysql);
      exit(1);
      }
    if(mysql_num_rows($result) != 0){
      $client_id = mysql_result($result, 0, "id");
      }
    else{
      header ("Location: show_error.php?error="."There is no client with login $login");
      exit(1);
      }
    }
 // If user has "client" privileges, check his validity
  if($_SESSION['authdata']['permlevel'] == 'client'){
    if(!check_client_validity($client_id, $mysql)){
      header($start_url);
      }
    }
  if($REQUEST_METHOD == "POST" && isset($_POST["vote"])){
    mysql_query("BEGIN");
    $query = "SELECT COUNT(*) AS num FROM voting WHERE client_id = '$client_id'";
    $result = mysql_query($query);
    if($result == FALSE){
      $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
      $msg = str_replace("\n", "<br>", $msg);
      $msg = urlencode($msg);
      header ("Location: show_error.php?error=".mysql_error());
      mysql_close($mysql);
      exit(1);
      }
    $num = mysql_result($result, 0, "num");
    if($num == 0){
      $choice = mysql_escape_string($_POST["choice"]);
      $notes = mysql_escape_string($_POST["notes"]);
      $query = "INSERT INTO `voting`(client_id, choice, notes) VALUES('$client_id', '$choice', '$notes')";
      if(mysql_query($query) == FALSE){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".mysql_error());
        mysql_close($mysql);
        exit(1);
        }
      }
    mysql_query("COMMIT");
    }
  if($REQUEST_METHOD == "POST" && isset($_POST["drweb"])){
 // read config
 $q = "SELECT attribute, value FROM config WHERE attribute like  'drweb_avd_%'";
 if(($r = mysql_query($q)) == FALSE){
 	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
 	$msg = str_replace("\n", "<br>", $msg);
 	$msg = urlencode($msg);
 	header ("Location: show_error.php?error=".mysql_error());
 	mysql_close($mysql);
 	exit(1);
 	}
 while($row = mysql_fetch_row($r)){
 	$config[$row[0]] = $row[1];
 	}
 $drweb_avd_host = $config["drweb_avd_host"];
 $drweb_avd_port = $config["drweb_avd_port"];
 $drweb_avd_user = $config["drweb_avd_user"];
 $drweb_avd_password = $config["drweb_avd_password"];
 // 
	if(isset($_POST["drweb_subscribe"])){
 		mysql_query("BEGIN");
		$client_id = mysql_escape_string($_POST["client_id"]);
		$newgroup = mysql_escape_string($_POST["newgroup"]);
		$contract_id = mysql_escape_string($_POST["contract_id"]);
		$newdescription = $client_id;
		
		$s = http_parse_message(http_get("http://".$drweb_avd_host.":".$drweb_avd_port."/avdesk/api/add-customer.ds?&pgrp=".urlencode($newgroup)."&desc=".urlencode(iconv("koi8-u", "utf-8", $client_id)).$access.$from.$till, array("httpauth"=> $drweb_avd_user.":".$drweb_avd_password)));
		$xmlstr = $s->body;
		//echo "$xmlstr"; exit(0);
		$sxe = new SimpleXMLElement($xmlstr);
		$sa = $sxe->attributes();
		if($sa["rc"] == "false"){
			$msg = "Error: ".$sa["message"]." while adding subscriber ".$client_id;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
			mysql_close($mysql);
			exit(1);
			}
		$sa = $sxe->customer->attributes();
		$url = $sa["url"];
		$id = $sa["id"];
		$newpassword = $sa["pwd"];
		// Get cost
 		$q = "SELECT cost FROM drweb_avd_group WHERE group_id = '$newgroup'";
 		if(($r = mysql_query($q)) == FALSE){
 			$msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
 			$msg = str_replace("\n", "<br>", $msg);
 			$msg = urlencode($msg);
			mysql_query("ROLLBACK");
 			header ("Location: show_error.php?error=".$msg);
 			mysql_close($mysql);
 			exit(1);
 			}
		if(mysql_num_rows($r) == 0){
 			$msg = "Error: There is no group '$newgroup'";
 			$msg = str_replace("\n", "<br>", $msg);
 			$msg = urlencode($msg);
			mysql_query("ROLLBACK");
 			header ("Location: show_error.php?error=".$msg);
 			mysql_close($mysql);
 			exit(1);
			}
		$row = mysql_fetch_row($r);
		$cost = $row[0];
		//
		// Insert new tariff
		$q = "INSERT INTO tariff(monthlypayment, tariff, main_currency) VALUES('yes', 'return $cost*rel_time(0);\n//$id', 'yes')";
 		if((mysql_query($q)) == FALSE){
 			$msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
 			$msg = str_replace("\n", "<br>", $msg);
 			$msg = urlencode($msg);
			mysql_query("ROLLBACK");
 			header ("Location: show_error.php?error=".$msg);
 			mysql_close($mysql);
 			exit(1);
			}
		$tariff_id = mysql_insert_id();
		//
		// Insert new service
		$q = "INSERT INTO service(description, contract_id, service_type_id, tariff_id, start_time, cash) 
			VALUES('Антив╕русний захист', '$contract_id', '1', '$tariff_id', DATE_ADD(NOW(), INTERVAL 15 DAY), 'no')";
 		if((mysql_query($q)) == FALSE){
 			$msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
 			$msg = str_replace("\n", "<br>", $msg);
 			$msg = urlencode($msg);
			mysql_query("ROLLBACK");
 			header ("Location: show_error.php?error=".$msg);
 			mysql_close($mysql);
 			exit(1);
			}
		$service_id = mysql_insert_id();
		$q = "INSERT INTO drweb_avd_customer
			(id, client_id, service_id, pwd, pgrp, `desc`, url) 
			VALUES('$id', '$client_id', '$service_id', '$newpassword', '$newgroup', '$newdescription', 
			'$url')";
		if(mysql_query($q) == FALSE){
			$msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			mysql_query("ROLLBACK");
			header ("Location: show_error.php?error=".$msg);
			mysql_close($mysql);
			exit(1);
			}
		mysql_query("COMMIT");
		}
	else{ // The customer pressed "Stop button"
  		$q = "SELECT id
		  FROM drweb_avd_customer
		  WHERE client_id = '$client_id'";
		if(($r = mysql_query($q)) == FALSE){
			$msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			mysql_query("ROLLBACK");
			header ("Location: show_error.php?error=".$msg);
			mysql_close($mysql);
			exit(1);
			}
		//print_r($_POST);
		//exit(0);
		while($row = mysql_fetch_row($r)){
			if($_POST[$row[0]] == "Stop"){
				$id = mysql_escape_string($row[0]);
				mysql_query("BEGIN");
				// Get service_id
				$q = "SELECT service_id FROM drweb_avd_customer WHERE id = '$id'";
				if(($r_service_id = mysql_query($q)) == FALSE){
					$msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
					$msg = str_replace("\n", "<br>", $msg);
					$msg = urlencode($msg);
					mysql_query("ROLLBACK");
					header ("Location: show_error.php?error=".$msg);
					mysql_close($mysql);
					exit(1);
					}
				$row_service_id = mysql_fetch_row($r_service_id);
				$service_id = $row_service_id[0];
				$q = "SELECT @LAST_DAY := DATE_ADD(LAST_DAY(IF(NOW() > start_time, NOW(), start_time)), INTERVAL 1 DAY) 
					FROM service 
					WHERE id = '$service_id'";
				if((mysql_query($q)) == FALSE){
					$msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
					$msg = str_replace("\n", "<br>", $msg);
					$msg = urlencode($msg);
					mysql_query("ROLLBACK");
					header ("Location: show_error.php?error=".$msg);
					mysql_close($mysql);
					exit(1);
					}
				// Update service
				$q = "UPDATE service SET expire_time = @LAST_DAY 
					WHERE id = '$service_id'";
				if((mysql_query($q)) == FALSE){
					$msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
					$msg = str_replace("\n", "<br>", $msg);
					$msg = urlencode($msg);
					mysql_query("ROLLBACK");
					header ("Location: show_error.php?error=".$msg);
					mysql_close($mysql);
					exit(1);
					}
				// Update drweb_avd_customer
				$q = "UPDATE drweb_avd_customer SET access_date = @LAST_DAY WHERE id = '$id'";
				if((mysql_query($q)) == FALSE){
					$msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
					$msg = str_replace("\n", "<br>", $msg);
					$msg = urlencode($msg);
					mysql_query("ROLLBACK");
					header ("Location: show_error.php?error=".$msg);
					mysql_close($mysql);
					exit(1);
					}
				// Update DrWEB AV-Desk server
				$q = "SELECT YEAR(@LAST_DAY), MONTH(@LAST_DAY), DAY(@LAST_DAY)";
				if(($r_last_day = mysql_query($q)) == FALSE){
					$msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
					$msg = str_replace("\n", "<br>", $msg);
					$msg = urlencode($msg);
					mysql_query("ROLLBACK");
					header ("Location: show_error.php?error=".$msg);
					mysql_close($mysql);
					exit(1);
					}
                                $row_last_day = mysql_fetch_row($r_last_day);
				$accessy1 = $row_last_day[0];
				$accessm1 = $row_last_day[1];
				$accessd1 = $row_last_day[2];
				$access = "&year=".urlencode($accessy1)."&month=".urlencode($accessm1)."&day=".urlencode($accessd1);
				$xml_url = "http://".$drweb_avd_host.":".$drweb_avd_port."/avdesk/api/set-expiration.ds?id=".urlencode($id).$access;
				$s = http_parse_message(http_get($xml_url, 
					array("httpauth"=> $drweb_avd_user.":".$drweb_avd_password)));
				$xmlstr = $s->body;
				echo "$xmlstr\n";
				$sxe = new SimpleXMLElement($xmlstr);
				$sa = $sxe->attributes();
				if($sa["rc"] == "false"){
					$msg = "Error: ".$sa["message"]." while setting access date for ".$id;
					$msg = str_replace("\n", "<br>", $msg);
					$msg = urlencode($msg);
					header ("Location: show_error.php?error=".$msg);
					mysql_query("ROLLBACK");
					mysql_close($mysql);
					exit(1);
					}

				mysql_query("COMMIT");
				}
			}
		}
    	}

  $query = "select client.id,
    client.description,
    client.manager_id,
    client.person,
    client.phone,
    client.email
    from client
    where client.id = '$client_id'";
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
    <td valign="top" align="right" colspan="2"><a href="logout.php">Выйти</a></td>
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
                <td width="40%">Контактна особа</td>
                <td width="60%"><? echo mysql_result($result, 0, "client.person");?></td>
              </tr>
              <tr bgcolor="white">
                <td>Контактний телефон</td>
                <td><? echo mysql_result($result, 0, "client.phone");?></td>
              </tr>
              <tr bgcolor="white">
                <td>Адреса электронно╖ пошти</td>
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
            <caption>мереж╕ кл╕╓нта</caption>
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
        <tr>
          <td>
            <table border="0" width="100%" cellspacing="1" cellpadding="2" bgcolor="silver">
            <caption>Баланс</caption>
<?
  $query = "
		SELECT
		contract.id,
		contract.c_type,
		contract.c_number,
		SUM(charge.value_without_vat) AS charged
		FROM contract
		LEFT JOIN service ON service.contract_id = contract.id
		LEFT JOIN charge ON charge.service_id = service.id
		WHERE contract.client_id = '$client_id'
		GROUP BY contract.id";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($result);
?>
              <tr bgcolor="white">
                <th>Номер Договору</th>
                <th align="right">Баланс з ПДВ, грн.</th>
              </tr>
<?
  $whole_balance = 0;
  $vat = get_vat($mysql);
  for($i = 0; $i < $n; $i++){
	  $contract_id = mysql_escape_string(mysql_result($result, $i, "contract.id"));
	  $c_type = mysql_result($result, $i, "contract.c_type");
	  $c_number = mysql_result($result, $i, "contract.c_number");
	  $charged = mysql_result($result, $i, "charged");
          $q = "SELECT
		SUM(payment.value_without_vat) AS paid
		FROM payment
		WHERE payment.contract_id = '$contract_id'";
	  $r = mysql_query($q);
	  if($r == FALSE){
		$msg = "Error: ".mysql_error()." while executing ".$query;
		header ("Location: show_error.php?error=".$msg);
		mysql_close($mysql);
		exit(1);
		}
	  if(mysql_num_rows($r) == 1){
		$paid = mysql_result($r, 0, "paid");
		}
	  else{
		$paid = 0;
		}
	  mysql_free_result($r);
	  $balance = round2($paid - $charged) * (1 + $vat);
	  $whole_balance = round2($whole_balance + $balance);
?>
            <tr bgcolor="white" onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'">
              <td><? echo $c_type." - ".$c_number;?></td>
	      <td align="right"><? echo number_format($balance, 2, ".", "");?></td>
	    </tr>
<?
  	}
?>
		<tr bgcolor="white">
			<td>Сумарно за вс╕ма договорами</td>
			<td colspan=1 align="right"><?if($whole_balance < 0){ echo "<font color=\"red\">"; }?><? echo number_format($whole_balance, 2, ".", "");?><?if($whole_balance < 0){ echo "</font>"; }?></td>
		</tr>
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
            <table border="0" width="100%" cellspacing="1" cellpadding="2" bgcolor="silver">
            <caption>╕нтерфейси кл╕╓нта</caption>
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
            <table>
              <tr><td><a href="ua-ix.php">Перел╕к мереж UA-IX</a></td></tr>
            </table>
          </td>
	</tr>
	<tr>
		<td>
		<form name="drweb_avd" method="POST" action="<? echo $_SERVER["SCRIPT_NAME"];?>">
		<table border="0" width="100%" cellspacing="1" cellpadding="2" bgcolor="silver">
		<caption>Антив╕русний захист DrWEB</caption>
			<tr>
				<th>ID</th><th>Пакет</th><th>Д╕йсний до</th>
			<tr>
<?
  	$q = "SELECT drweb_avd_customer.id,
	  drweb_avd_group.name,
	  IF(drweb_avd_customer.access_date = 0, 1, 0), 
	  drweb_avd_customer.url,
	  drweb_avd_customer.access_date
	  FROM drweb_avd_customer
	  LEFT JOIN drweb_avd_group ON drweb_avd_group.group_id = drweb_avd_customer.pgrp
	  WHERE client_id = '$client_id' ORDER BY drweb_avd_customer.access_date DESC";
	if(($r = mysql_query($q)) == FALSE){
		$msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".mysql_error());
		mysql_close($mysql);
		exit(1);
		}
	while($row = mysql_fetch_row($r)){	
?>
		<tr bgcolor="white">
			<td><a href="<? echo $row[3]; ?>"><? echo $row[0]; ?></a></td>
			<td><? echo $row[1]; ?></td>
			<td><?
	if($row[2] == 1){
?>
	<input type="submit" name="<? echo $row[0]; ?>" value="Stop">
<?
		}
	else{
		echo "$row[4]";
		}
?>
</td>
		</tr>
<?
		}
?>
		<tr bgcolor="white">
			<td><select name="contract_id">
<?
	$q = "SELECT id, c_type, c_number FROM contract WHERE client_id='$client_id'";
	if(($r = mysql_query($q)) == FALSE){
		$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".mysql_error());
		mysql_close($mysql);
		exit(1);
		}
	while($row = mysql_fetch_row($r)){
		echo "<option value=\"".$row[0]."\">".$row[1]."-".$row[2]."</option>\n";
		}
?>
			</select>
			<td><select name="newgroup">
<?
	$q = "SELECT group_id, name, cost FROM drweb_avd_group";
	if(($r = mysql_query($q)) == FALSE){
		$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".mysql_error());
		mysql_close($mysql);
		exit(1);
		}
	while($row = mysql_fetch_row($r)){
		echo "<option value=\"".$row[0]."\">".$row[1]." | Варт╕сть ".$row[2]." грн/м╕сяць без ПДВ</option>\n";
		}
?>
			</select>
            		<input type="hidden" name="client_id" value="<?echo $client_id?>">
            		<input type="hidden" name="cl_login" value="<?echo $cl_login?>">
            		<input type="hidden" name="drweb" value="on">
</td>
			<td><input type="submit" name="drweb_subscribe" value="Замовити" onclick="return confirm('П╕дтверд╕ть, що Ви прочитали та згодн╕ з Л╕ценз╕йною Угодою DrWEB Ltd.');"></td>
		</tr>
		<tr bgcolor="white"><td colspan="3"><a href="drweblicense.html">Л╕ценз╕йна Угода DrWEB Ltd.</a></td></tr>
		</table>
		</form>
		</td>
	</tr>
        <tr>
          <td>
            <form action="subscribe.php" method="post">
            <table border="0" width="100%" cellspacing="1" cellpadding="2" bgcolor="silver">
            <caption>&nbsp;</caption>
              <tr bgcolor="white">
                <td align="center" colspan="2"><a href="order_detailed_report.php?client_id=<?echo $client_id?>&amp;cl_login=<?echo $login?>">Форма замовлення детально╖ статистики</a></td>
              </tr>
              <tr bgcolor="white">
                <td align="center" colspan="2"><a href="detailed_report.php?client_id=<?echo $client_id?>&amp;cl_login=<?echo $login?>">Зв╕ти</a></td>
              </tr>
              <tr bgcolor="white">
                <td colspan="2">П╕дпиш╕ться на розсилку NetStore, щоб щоденно отримувати зв╕ти про траф╕к</td>
              </tr>
              <tr bgcolor="white">
                <td>E-mail</td>
                <td><input type="text" name="email"></td>
              </tr>
              <tr bgcolor="white">
                <td><input type="radio" name="subscribe" value="yes" id="radio_subsribe_yes" checked></td>
                <td><label for="radio_subsribe_yes">п╕дписатися</label></td>
              </tr>
              <tr bgcolor="white">
                <td><input type="radio" name="subscribe" value="no" id="radio_subsribe_no"></td>
                <td><label for="radio_subsribe_no">в╕дписатися</label></td>
              </tr>
              <tr bgcolor="white">
                <th colspan="2"><input type="submit" name="submit_form_subscribe" value="п╕дтвердити"></th>
              </tr>
            </table>
            <input type="hidden" name="client_id" value="<?echo $client_id?>">
            <input type="hidden" name="cl_login" value="<?echo $cl_login?>">
            </form>
          </td>
        </tr>
      </table>
    </td>

    <td width="75%" valign="top">
      <table border="0" width="100%">
        <tr>
          <td>
            <table border="0" width="100%">
<?
  $qry_last_update = "select max(timestamp) as lastupdate from feeding";
  $res_last_update = mysql_query($qry_last_update);
  if($res_last_update == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $n_last_update = mysql_num_rows($res_last_update);
  if($n_last_update == 1){
    $last_update = "(Останн╓ поновлення: ".mysql_result($res_last_update, 0, "lastupdate").")";
    }
  // Whole traffic
  $qry_whole = "SELECT
      DATE_FORMAT(traffic_snapshot.timestamp, '%Y-%m-%d') as day,
      SUM(traffic_snapshot.incoming) AS incoming,
      SUM(traffic_snapshot.outcoming) AS outcoming
      FROM traffic_snapshot
      WHERE traffic_snapshot.client_id = $client_id
      AND UNIX_TIMESTAMP(traffic_snapshot.timestamp) >= $dbegin
      AND UNIX_TIMESTAMP(traffic_snapshot.timestamp) < $dend
      GROUP BY day";
  //echo $qry_whole;
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
      
  if($factor == 1 || $factor == 1024) $prec = 0;
  if($factor == 1048576 || $factor == 1073741824) $prec = 4;
      
?>
              <tr bgcolor="white">
                <td>
                  <form method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
                  <table width="100%">
                    <tr>
                      <td colspan="3">
                        <table>
                          <tr>
                            <th align="right">Пер╕од:</th>
                            <td colspan="2"><?echo strftime("%e %B %Y %X (%Z)",$dbegin)?> - <?echo strftime("%e %B %Y %X (%Z)",$dend - 1)?></td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    <tr>
                      <td><? echo $last_update?></td>
                      <td align="right">показати в</td>
                      <td>
                        <select name="factor" onchange="this.form.submit()">
                          <option value="1073741824" <? if($factor == 1073741824) echo "selected"?> >г╕габайтах</option>
                          <option value="1048576" <? if($factor == 1048576) echo "selected"?>>мегабайтах</option>
                          <option value="1024" <? if($factor == 1024) echo "selected"?>>к╕лобайтах</option>
                          <option value="1" <? if($factor == 1) echo "selected"?>>байтах</option>
                        </select>
                        <input type="hidden" name="client_id" value="<? echo $client_id ?>">
                        <input type="hidden" name="dbegin" value="<? echo $dbegin ?>">
                        <input type="hidden" name="dend" value="<? echo $dend ?>">
                        <input type="hidden" name="cl_login" value="<? echo $login ?>">
                      </td>
                    </tr>
                  </table>
                  </form>
                </td>
              </tr>
              
              <tr bgcolor="white">
                <td>
                  <table border="0" width="100%" cellspacing="1" cellpadding="2" bgcolor="silver">
                    <tr bgcolor="#D3D3D3">
                      <th colspan="3">Загальний траф╕к</th>
                    </tr>
                    <tr bgcolor="#E4E4E4">
                      <td align="right"><i>Дата</i></td>
                      <td align="right"><i>Вх╕дний</i></td>
                      <td align="right"><i>Вих╕дний</i></td>
                    </tr>
<?
  $sum_full_incoming = 0;
  $sum_full_outcoming = 0;
  for($i = 0; $i < $n_whole; $i++){
    $full_incoming = mysql_result($res_whole, $i, "incoming");
    $full_outcoming = mysql_result($res_whole, $i, "outcoming");
    $sum_full_incoming += $full_incoming;
    $sum_full_outcoming += $full_outcoming;
?>
                    <tr bgcolor="white">
                      <td bgcolor="ffffcc" align="right" width="34%"><?echo mysql_result($res_whole, $i, "day")?></td>
                      <td align="right" width="33%"><?echo number_format($full_incoming/$factor, $prec, ".", " ")?></td>
                      <td align="right" width="33%"><?echo number_format($full_outcoming/$factor, $prec, ".", " ")?></td>
                    </tr>
<?
    }
?>
                    <tr bgcolor="white">
                      <td bgcolor="#F5F5F5" colspan="3" align="center">Сума по вс╕м дням</td>
                    </tr>
                    <tr bgcolor="white">
                      <td bgcolor="#ffffcc" align="right" width="34%"><b><?echo number_format(($sum_full_incoming + $sum_full_outcoming)/$factor, $prec, ".", " ")?></b></td>
                      <td bgcolor="#E4E4E4" align="right" width="33%"><?echo number_format($sum_full_incoming/$factor, $prec, ".", " ")?></td>
                      <td bgcolor="#E4E4E4" align="right" width="33%"><?echo number_format($sum_full_outcoming/$factor, $prec, ".", " ")?></td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td>&nbsp;</td>
              </tr>
<?
  if($permlevel == 'client'){
  	$hidden_condition = "AND filter.hidden = 'no'";
  }
  else{
	$hidden_condition = "";
  	}
  $query = "SELECT 
      filter.id,
      filter.client_id,
      filter.description,
      DATE_FORMAT(filter_counter_snapshot.timestamp, '%Y-%m-%d') as day,
      SUM(filter_counter_snapshot.incoming) as incoming,
      SUM(filter_counter_snapshot.outcoming) as outcoming
      from filter
      left join filter_counter_snapshot on filter.id = filter_counter_snapshot.filter_id
      WHERE filter.client_id = $client_id
      AND UNIX_TIMESTAMP(filter_counter_snapshot.timestamp) >= $dbegin
      AND UNIX_TIMESTAMP(filter_counter_snapshot.timestamp) < $dend
      $hidden_condition
      GROUP BY filter.id, day";                              
  //  echo $query;
  $res = mysql_query($query);
  if($res == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $n_filter = mysql_num_rows($res);
  $description = "none";
  $description_prev = "prevnone";
  if($n_filter != 0){
  $sum_full_incoming = 0;
  $sum_full_outcoming = 0;
    for($i = 0; $i < $n_filter; $i++){
      $description = mysql_result($res, $i, "filter.description");
      $incoming = mysql_result($res, $i, "incoming");
      $outcoming = mysql_result($res, $i, "outcoming");
      $sum_full_incoming += $incoming;
      $sum_full_outcoming += $outcoming;
      if($description != $description_prev){
        $description_prev = $description;
?>
              <tr bgcolor="white">
                <td>
                  <table border="0" width="100%" cellspacing="1" cellpadding="2" bgcolor="silver">
                    <tr bgcolor="#D3D3D3">
                      <th colspan="3"><? echo $description; ?></th>
                    </tr>
                    <tr bgcolor="#E4E4E4">
                      <td align="right"><i>Дата</i></td>
                      <td align="right"><i>Вх╕дний</i></td>
                      <td align="right"><i>Вих╕дний</i></td>
                    </tr>
        
<?
        }
?>
                    <tr bgcolor="white">
                      <td align="right"bgcolor="ffffcc" width="34%"><? echo mysql_result($res, $i, "day");?></td>
                      <td align="right" width="33%"><? echo number_format($incoming/$factor, $prec, ".", " ")?></td>
                      <td align="right" width="33%"><? echo number_format($outcoming/$factor, $prec, ".", " ")?></td>
                    </tr>
<?
      if($i + 1 == $n_filter){
?>
                    <tr bgcolor="white">
                      <td bgcolor="#F5F5F5" colspan="3" align="center">Сума по вс╕м дням</td>
                    </tr>
                    <tr bgcolor="white">
                      <td bgcolor="#ffffcc" align="right" width="34%"><b><?echo number_format(($sum_full_incoming + $sum_full_outcoming)/$factor, $prec, ".", " ")?></b></td>
                      <td bgcolor="#E4E4E4" align="right" width="33%"><?echo number_format($sum_full_incoming/$factor, $prec, ".", " ")?></td>
                      <td bgcolor="#E4E4E4" align="right" width="33%"><?echo number_format($sum_full_outcoming/$factor, $prec, ".", " ")?></td>
                    </tr>
                  </table>
                </td>
              </tr>
<?
        $sum_full_incoming = 0;
        $sum_full_outcoming = 0;
        }
      else{
        if(mysql_result($res, $i, "filter.id") != mysql_result($res, $i + 1, "filter.id")){
?>
                    <tr bgcolor="white">
                      <td bgcolor="#F5F5F5" colspan="3" align="center">Сума по вс╕м дням</td>
                    </tr>
                    <tr bgcolor="white">
                      <td bgcolor="#ffffcc" align="right" width="34%"><b><?echo number_format(($sum_full_incoming + $sum_full_outcoming)/$factor, $prec, ".", " ")?></b></td>
                      <td bgcolor="#E4E4E4" align="right" width="33%"><?echo number_format($sum_full_incoming/$factor, $prec, ".", " ")?></td>
                      <td bgcolor="#E4E4E4" align="right" width="33%"><?echo number_format($sum_full_outcoming/$factor, $prec, ".", " ")?></td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td>&nbsp;</td>
              </tr>
<?
          $sum_full_incoming = 0;
          $sum_full_outcoming = 0;
          }
        }
      }
    }
  // Generatin graphs
  $graph_prefix = "rrd_";
  $daily = "daily".session_id().".png";
  $daily_big = "dailybig".session_id().".png";
  $daily_start = $dend - 24 * 2 * 3600;
  @unlink("/tmp/".$graph_prefix.$daily);
  $cmd = "/usr/local/bin/rrdtool graph /tmp/".escapeshellarg($graph_prefix.$daily)." \
      -s ".escapeshellarg($daily_start)." \
      --vertical-label \"Bytes per Second\" \
      --title \"Bandwidth Usage\" \
      DEF:ifInOctets=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:ifInOctets:AVERAGE \
      DEF:ifOutOctets=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:ifOutOctets:AVERAGE \
      LINE2:ifInOctets#00FF00:\"Incoming traffic\" \
      LINE2:ifOutOctets#0000FF:\"Outgoing traffic\" ";  
  exec($cmd);
  if(file_exists("/tmp/".$graph_prefix.$daily) && date("m") == date("m", $dbegin)){
?>
              <tr>
                <td>&nbsp;</td>
              </tr>
              <tr bgcolor="white">
                <td>
                  <table border="0" width="100%" cellspacing="1" cellpadding="2" bgcolor="silver">
                    <tr bgcolor="#D3D3D3">
                      <th>Використання полоси пропускання</th>
                    </tr>
                    <tr bgcolor="#F5F5F5">
                      <th>За останн╕ дв╕ доби</th>
                    </tr>
                    <tr bgcolor="#F5F5F5">
                      <th><a href="show_graphs.php?dbegin=<? echo $dend - 24 * 2 * 3600;; ?>&amp;dend=<? echo $dend; ?>&amp;client_id=<? echo $client_id; ?>"><img src="<? echo $graph_prefix.$daily; ?>"></a></th>
                    </tr>
                  </table>
                </td>
              </tr>
<?
    }
  $monthly = "monthly".session_id().".png";
  @unlink("/tmp/".$graph_prefix.$monthly);
  $cmd = "/usr/local/bin/rrdtool graph /tmp/".escapeshellarg($graph_prefix.$monthly)." \
      -s ".escapeshellarg($dbegin)." -e ".escapeshellarg($dend)." \
      --vertical-label \"Bytes per Second\" \
      --title \"Bandwidth Usage\" \
      DEF:ifInOctets=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:ifInOctets:AVERAGE \
      DEF:ifOutOctets=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:ifOutOctets:AVERAGE \
      LINE2:ifInOctets#00FF00:\"Incoming traffic\" \
      LINE2:ifOutOctets#0000FF:\"Outgoing traffic\" ";  
  exec($cmd);
  if(file_exists("/tmp/".$graph_prefix.$monthly)){
?>
              <tr>
                <td>&nbsp;</td>
              </tr>
              <tr bgcolor="white">
                <td>
                  <table border="0" width="100%" cellspacing="1" cellpadding="2" bgcolor="silver">
                    <tr bgcolor="#F5F5F5">
                      <th>За пер╕од з <?echo strftime("%e %B %Y %X (%Z)",$dbegin)?> по <?echo strftime("%e %B %Y %X (%Z)",$dend - 1)?> </th>
                    </tr>
                    <tr bgcolor="#F5F5F5">
                      <th><a href="show_graphs.php?dbegin=<? echo $dbegin; ?>&amp;dend=<? echo $dend; ?>&amp;client_id=<? echo $client_id; ?>"><img src="<? echo $graph_prefix.$monthly; ?>"></a></th>
                    </tr>
                  </table>
                </td>
              </tr>
<?
    }
?>
              <tr bgcolor="white">
                <td align="center">
                  <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
                  <caption>Арх╕в зв╕т╕в по м╕сяцям</caption>
<?
  $m = date("m");
  for($i = 0; $i < 4 ; $i++){
    echo "
                    <tr bgcolor=\"white\">";
    for($j = 0; $j < 3 ; $j++){
      $month = strftime("%b/%Y",mktime(0,0,0,$m,1,date("Y")));
      $dbegin = mktime(0, 0, 0, $m, 1, date("Y"));
      $dend = mktime(0, 0, 0, $m + 1 , 1, date("Y"));
      if($dend > mktime()){
        $dend = mktime();
        }
      echo "
                      <td><a href=\"reportpersonal.php?dbegin=$dbegin&amp;dend=$dend&amp;client_id=$client_id&amp;factor=$factor&amp;cl_login=$login\">$month</a></td>";
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
