<?
	$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
	readfile("$DOCUMENT_ROOT/head.html");
	include("$DOCUMENT_ROOT/netstorecore.php");
	session_start();
	if(!@session_is_registered('authdata')){
		header($start_url);
		exit(1);
		}
	if($_SESSION['authdata']['permlevel'] != 'admin' 
			&& $_SESSION['authdata']['permlevel'] != 'manager' 
			&& $_SESSION['authdata']['permlevel'] != 'client' 
			&& $_SESSION['authdata']['permlevel'] != 'topmanager'){
		header($start_url);
		exit(1);
		}
	$authdata = $_SESSION['authdata'];
	include("top.php");
	$login = $authdata['login'];
	$passwd = $authdata['passwd'];
	$permlevel = $authdata['permlevel'];
	$mysql = @mysql_connect($host, $login, $passwd); mysql_set_charset("koi8u");
	if($mysql == FALSE){
		header ("Location: show_error.php?error="."Cannot connect to mysql server");
		exit(1);
		}
	if(FALSE == mysql_select_db($db)){
		header ("Location: show_error.php?error=".mysql_error());
		mysql_close($mysql);
		exit(1);
		}
	if(isset($_POST["client_id"])){
		$client_id = mysql_escape_string($_POST["client_id"]);
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
	// If user has "client" privileges, check his validity
	if($_SESSION['authdata']['permlevel'] == 'client'){
		if(!check_client_validity($client_id, $mysql)){
			header($start_url);
			exit(1);
			}
		}

	if($_SESSION['authdata']['permlevel'] == 'client'){
?>
 <tr>
 	<td valign="top" align="right"><a href="logout.php">Выйти</a></td>
 </tr>
 <?
 	}
 // Check MX's
 // At least one relay MUST belong to allowed network. 
 // (see netstorecore.php, arrays allowed_network and allowed_netmask)
	$report_type = mysql_escape_string($_POST["report_type"]);
	$accepted = true;
	if($report_type == 'flows'){
		$resultemail = mysql_escape_string($_POST["resultemail"]);
		$domen = substr(strstr($resultemail, '@'), 1).".";
		//echo "$domen<br>";
		$accepted = false;
		if(FALSE == getmxrr($domen, $relays)){
			$err_msg = "Can't find any MX record for domain. Possibly incorrect e-mail $resultemail.";
			header ("Location: show_error.php?error=".$err_msg);
			exit(1);
			}
		else{
			for($i = 0; $i < count($relays); $i++){
				$relay = $relays[$i];
				//echo "$relay<br>";
				if(($ip_relay = gethostbyname($relay)) == $relay){
					$err_msg = "Can't determine ip address of $relay.";
					header ("Location: show_error.php?error=".$err_msg);
					exit(1);
					}
				else{
					for($j = 0; $j < count($allowed_network); $j++){
						//echo "Checking IP: $ip_relay network($allowed_network[$j]/$allowed_netmask[$j])<br>";
						if((ip2long($ip_relay) & ip2long($allowed_netmask[$j])) == ip2long($allowed_network[$j])){
							$accepted = true;
							break;
							}
						}
					}
				}
			}
		if(!$accepted){
			if($err_msg == ""){
				$err_msg = "Ни один из почтовых серверов, обслуживающих почту домена $domen не находится в сети ".$company_name;
				}
			$err_msg .= " Указанный почтовый адреc $resultemail не принят.";
			}
		}
	$publish = ($_POST["post2site"] == "on") ? "yes" : "no";
	$resolve_ip = ($_POST["resolve_ip"] == "on") ? "yes" : "no";
	$report_type = mysql_escape_string($_POST["report_type"]);
	$periody1 = mysql_escape_string($_POST["periody1"]);
	$periodm1 = mysql_escape_string($_POST["periodm1"]);
	$periodd1 = mysql_escape_string($_POST["periodd1"]);
	$periody2 = mysql_escape_string($_POST["periody2"]);
	$periodm2 = mysql_escape_string($_POST["periodm2"]);
	$periodd2 = mysql_escape_string($_POST["periodd2"]);
	$resultemail = mysql_escape_string($_POST["resultemail"]);
	$notifyemail = mysql_escape_string($_POST["notifyemail"]);
	$operator = mysql_escape_string($_POST["operator"]);
	$notifycell = mysql_escape_string($_POST["notifycell"]);
	$archtype = mysql_escape_string($_POST["archtype"]);
	$query = "
			INSERT INTO order_report
			(client_id,
			report_type,
			starttimestamp,
			stoptimestamp,
			result_email,
			notify_email,
			publish,
			cell_operator,
			cell_phone,
			arch_type,
			resolve_ip)
			VALUES(
	$client_id,
	'$report_type',
	'$periody1-$periodm1-$periodd1 00:00:00',
	'$periody2-$periodm2-$periodd2 23:59:59',
	'$resultemail',
	'$notifyemail',
	'$publish',
	'$operator',
	'$notifycell',
	'$archtype',
	'$resolve_ip')";
 if($accepted){
 	if(FALSE == mysql_query($query)){
 		header ("Location: show_error.php?error=".mysql_error());
		mysql_close($mysql);
		exit(1);
		}
	}
 ?>
 <tr>
 	<td>
 		<table border="0" width="100%">
			<tr>
				<td>
				<?
				if($accepted){
					echo "Благодарим Вас за использование формы заказа детальной статистики. Ваш заказ поставлен в очередь на исполнение.";
					}
				else{
					header("Location: show_error.php?error=".$err_msg);
					exit(1);
					}
				?>
				</td>
			</tr>
			<tr>
<?
	$dfrom = mktime(0, 0, 0, date("m"), 1, date("Y"));
	$dto = mktime();
?>
				<td><a href="reportpersonal.php?client_id=<? echo $client_id?>&amp;dbegin=<? echo $dfrom?>&amp;dend=<? echo $dto?>">Вернуться на начальную страницу</a></td>
			</tr>
		</table>
	</td>
 </tr>
 <?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
