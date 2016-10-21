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
	if($REQUEST_METHOD == "POST"){
		$d1 = mysql_escape_string($_POST["d1"]);
		$m1 = mysql_escape_string($_POST["m1"]);
		$y1 = mysql_escape_string($_POST["y1"]);
		$d2 = mysql_escape_string($_POST["d2"]);
		$m2 = mysql_escape_string($_POST["m2"]);
		$y2 = mysql_escape_string($_POST["y2"]);
		$activation_time = ($d1 == 0 || $m1 == 0 || $y1 == 0)? "NULL" : "'"."$y1-$m1-$d1"."'";
		$inactivation_time = ($d2 == 0 || $m2 == 0 || $y2 == 0)? "NULL" : "'"."$y2-$m2-$d2"."'";
		$client_id = mysql_escape_string($_POST["client_id"]);
		$full_name = mysql_escape_string($_POST["full_name"]);
		$description = mysql_escape_string($_POST["description"]);
		$director = mysql_escape_string($_POST["director"]);
		$person = mysql_escape_string($_POST["person"]);
		$edrpou = mysql_escape_string($_POST["edrpou"]);
		$tax_number = mysql_escape_string($_POST["tax_number"]);
		$licence_number = mysql_escape_string($_POST["licence_number"]);
		$tac_status = mysql_escape_string($_POST["tac_status"]) == "on" ? "y": "n";
		$natural_person = mysql_escape_string($_POST["natural_person"]) == "on" ? "yes": "no";
		$phone = mysql_escape_string($_POST["phone"]);
		$fax = mysql_escape_string($_POST["fax"]);
		$email = mysql_escape_string($_POST["email"]);
		$bank_name = mysql_escape_string($_POST["bank_name"]);
		$mfo = mysql_escape_string($_POST["mfo"]);
		$account = mysql_escape_string($_POST["account"]);
		$phys_zip = mysql_escape_string($_POST["phys_zip"]);
		$jur_zip = mysql_escape_string($_POST["jur_zip"]);
		$phys_addr_1 = mysql_escape_string($_POST["phys_addr_1"]);
		$phys_addr_2 = mysql_escape_string($_POST["phys_addr_2"]);
		$phys_addr_3 = mysql_escape_string($_POST["phys_addr_3"]);
		$jur_addr_1 = mysql_escape_string($_POST["jur_addr_1"]);
		$jur_addr_2 = mysql_escape_string($_POST["jur_addr_2"]);
		$jur_addr_3 = mysql_escape_string($_POST["jur_addr_3"]);
		mysql_query("BEGIN");
		$query = "UPDATE client SET
				full_name = '$full_name',
				description = '$description',
				director = '$director',
				person = '$person',
				edrpou = '$edrpou',
				tax_number = '$tax_number',
				licence_number = '$licence_number',
				tac_status = '$tac_status',
				natural_person = '$natural_person',
				phone = '$phone',
				fax = '$fax',
				email = '$email',
				bank_name = '$bank_name',
				mfo = '$mfo',
				account = '$account',
				phys_zip = '$phys_zip',
				jur_zip = '$jur_zip',
				phys_addr_1 = '$phys_addr_1',
				phys_addr_2 = '$phys_addr_2',
				phys_addr_3 = '$phys_addr_3',
				jur_addr_1 = '$jur_addr_1',
				jur_addr_2 = '$jur_addr_2',
				jur_addr_3 = '$jur_addr_3',
				activation_time = $activation_time,
				inactivation_time = $inactivation_time
				WHERE id = '$client_id'";
		if(mysql_query($query) == FALSE){
			$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".mysql_error());
		  mysql_close($mysql);
		  exit(1);
		  }
		mysql_query("COMMIT");
		//log_event($mysql, $client_id, $table, $tkey, $action_type, $details)
		log_event($mysql, $client_id, "client", $client_id, "update", "Update proreties of the client id $client_id.\nQuery:\n".$query);
		header("Location: show_properties.php?client_id=".$client_id);
		exit(0);
		}
	$client_id = mysql_escape_string($_GET["client_id"]);
  $query = "SELECT *, 
			UNIX_TIMESTAMP( activation_time ) AS unix_activation_time,  
			UNIX_TIMESTAMP( inactivation_time ) AS unix_inactivation_time
			FROM client WHERE client.id = '$client_id'";
	log_event($mysql, $client_id, "client", $client_id, "view", "Get properties of the client id $client_id");
  $result = mysql_query($query);
  if($result == FALSE){
		$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $responsible_manager = mysql_result($result, 0, "manager_id");
  if($permlevel == 'manager'){
		$readonlystatus = "readonly";
    if($login != $responsible_manager){
      header($start_url);
      }
    }
  if($responsible_manager == ''){
    $responsible_manager = 'Нет';
    }
	if(mysql_num_rows($result) == 1){
		$cl_login = mysql_result($result, 0, "login");
		$full_name = htmlspecialchars(mysql_result($result, 0, "full_name"), ENT_QUOTES, "KOI8-R");
		$description = htmlspecialchars (mysql_result($result, 0, "description"), ENT_QUOTES, "KOI8-R");
		$director = htmlspecialchars (mysql_result($result, 0, "director"), ENT_QUOTES, "KOI8-R");
		$person = htmlspecialchars (mysql_result($result, 0, "person"), ENT_QUOTES, "KOI8-R");
		$edrpou = htmlspecialchars (mysql_result($result, 0, "edrpou"), ENT_QUOTES, "KOI8-R");
		$tax_number = htmlspecialchars (mysql_result($result, 0, "tax_number"), ENT_QUOTES, "KOI8-R");
		$licence_number = htmlspecialchars (mysql_result($result, 0, "licence_number"), ENT_QUOTES, "KOI8-R");
		$tac_status = htmlspecialchars (mysql_result($result, 0, "tac_status"), ENT_QUOTES, "KOI8-R");
		$natural_person = htmlspecialchars (mysql_result($result, 0, "natural_person"), ENT_QUOTES, "KOI8-R");
		$phone = htmlspecialchars (mysql_result($result, 0, "phone"), ENT_QUOTES, "KOI8-R");
		$fax = htmlspecialchars (mysql_result($result, 0, "fax"), ENT_QUOTES, "KOI8-R");
		$email = htmlspecialchars (mysql_result($result, 0, "email"), ENT_QUOTES, "KOI8-R");
		$bank_name = htmlspecialchars (mysql_result($result, 0, "bank_name"), ENT_QUOTES, "KOI8-R");
		$mfo = htmlspecialchars (mysql_result($result, 0, "mfo"), ENT_QUOTES, "KOI8-R");
		$account = htmlspecialchars (mysql_result($result, 0, "account"), ENT_QUOTES, "KOI8-R");
		$phys_zip = htmlspecialchars (mysql_result($result, 0, "phys_zip"), ENT_QUOTES, "KOI8-R");
		$jur_zip = htmlspecialchars (mysql_result($result, 0, "jur_zip"), ENT_QUOTES, "KOI8-R");
		$phys_addr_1 = htmlspecialchars (mysql_result($result, 0, "phys_addr_1"), ENT_QUOTES, "KOI8-R");
		$phys_addr_2 = htmlspecialchars (mysql_result($result, 0, "phys_addr_2"), ENT_QUOTES, "KOI8-R");
		$phys_addr_3 = htmlspecialchars (mysql_result($result, 0, "phys_addr_3"), ENT_QUOTES, "KOI8-R");
		$jur_addr_1 = htmlspecialchars (mysql_result($result, 0, "jur_addr_1"), ENT_QUOTES, "KOI8-R");
		$jur_addr_2 = htmlspecialchars (mysql_result($result, 0, "jur_addr_2"), ENT_QUOTES, "KOI8-R");
		$jur_addr_3 = htmlspecialchars (mysql_result($result, 0, "jur_addr_3"), ENT_QUOTES, "KOI8-R");
		
		$unix_activation_time = mysql_result($result, 0, "unix_activation_time");
		$unix_inactivation_time = mysql_result($result, 0, "unix_inactivation_time");
		if($unix_activation_time != 0){
			$d1 = date("j", $unix_activation_time);
			$m1 = date("n", $unix_activation_time);
			$y1 = date("Y", $unix_activation_time);
			}
		else{
			$d1 = 0;
			$m1 = 0;
			$y1 = 0;
			}
		if($unix_inactivation_time != 0){
			$d2 = date("j", $unix_inactivation_time);
			$m2 = date("n", $unix_inactivation_time);
			$y2 = date("Y", $unix_inactivation_time);
			}
		else{
			$d2 = 0;
			$m2 = 0;
			$y2 = 0;
			
			}
		$activation_time = strftime("%d %B %Y року", $unix_activation_time);
		if($unix_inactivation_time == 0){
			$inactivation_time = "-";
			}
		else{
			$inactivation_time = strftime("%d %B %Y року", $unix_inactivation_time);
			}
		}
?>
  <tr>
		<td valign="top" width="20%">
<? include("left_cl.php"); ?>
		</td>
    <form method="POST" action="<? echo $_SERVER["PHP_SELF"];?>">
    <td align="center" valign="top">
      <table width="100%" cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
      <caption>Рекв╕зити споживача <i>(лог╕н <? echo "<b>".$cl_login."</b>";?>)</i></caption>
				<tr bgcolor="lightgrey">
					<td>Дата п╕дключення
						<select name="d1">
<?
	for($j = 1; $j <= 31; $j++){
?>
							<option <? if($j == $d1){ echo "selected"; }?>><? echo $j;?></option>
<?
		}
?>											
						</select>
						<select name="m1">
<?
	for($j = 1; $j <= 12; $j++){
?>
							<option <? if($j == $m1){ echo "selected"; }?> value="<? echo $j;?>"><? echo strftime("%B", mktime(0, 0, 0, $j, 1, 2000));?></option>
<?
		}
?>
						</select>
						<select name="y1">
<?
	$y = date("Y");
	for($j = $y - 5; $j <= $y + 5; $j++){
?>
							<option <? if($j == $y1){ echo "selected"; }?>><? echo $j;?></option>
<?
		}
?>
						</select>																			
					</td>
					<td>Дата в╕дключення
						<select name="d2">
							<option <? if($d2 == 0){ echo "selected"; }?> value="0">-</option>
<?
	for($j = 1; $j <= 31; $j++){
?>
							<option <? if($j == $d2){ echo "selected"; }?>><? echo $j;?></option>
<?
		}
?>											
						</select>
						<select name="m2">
							<option <? if($m2 == 0){ echo "selected"; }?> value="0">-</option>
<?
	for($j = 1; $j <= 12; $j++){
?>
							<option <? if($j == $m2){ echo "selected"; }?> value="<? echo $j;?>"><? echo strftime("%B", mktime(0, 0, 0, $j, 1, 2000));?></option>
<?
		}
?>
						</select>
						<select name="y2">
							<option <? if($y2 == 0){ echo "selected"; }?> value="0">-</option>
<?
	$y = date("Y");
	for($j = $y - 5; $j <= $y + 5; $j++){
?>
							<option <? if($j == $y2){ echo "selected"; }?>><? echo $j;?></option>
<?
		}
?>
						</select>																			
					</td>
				</tr>
				<tr bgcolor="white">
          <td>
						<table border="0" width="100%">
							<tr>
          			<th>Скорочена назва</th>
								<td><input type="text" name="description" <? echo $readonlystatus; ?> value="<? echo $description; ?>"></td>
          		</tr>
							<tr>
								<th>Повна назва</th>
								<td><input type="text" name="full_name" <? echo $readonlystatus; ?> value="<? echo $full_name; ?>"></td>
							</tr>
							<tr>
								<th>Кер╕вник</th>
								<td><input еype="text" name="director" <? echo $readonlystatus; ?> value="<? echo $director; ?>"></td>
							</tr>
							<tr>
								<th>Контактна особа</th>
								<td><input еype="text" name="person" <? echo $readonlystatus; ?> value="<? echo $person; ?>"></td>
							</tr>
							<tr>
								<th>╢ДРПОУ</th>
								<td><input type="text" name="edrpou" <? echo $readonlystatus; ?> value="<? echo $edrpou; ?>"></td>
							</tr>
							<tr>
          			<th>Податковий номер</th>
								<td><input type="text" name="tax_number" <? echo $readonlystatus; ?> value="<? echo $tax_number; ?>"></td>
          		</tr>
							<tr>
								<th>Номер свiдоцтва ПДВ</th>
								<td><input type="text" name="licence_number" <? echo $readonlystatus; ?> value="<? echo $licence_number; ?>"></td>
							</tr>
							<tr>
								<th>Платник ПДВ</th>
								<td><input type="checkbox" name="tac_status" <? echo $readonlystatus; ?> <? if($tac_status == "y") echo "checked"; ?>></td>
							</tr>
							<tr>
								<th>Ф╕зична особа</th>
								<td><input type="checkbox" name="natural_person" <? echo $readonlystatus; ?> <? if($natural_person == "yes") echo "checked"; ?>></td>
							</tr>
						</table>
					</td>
					<td valign="top">
						<table border="0" width="100%">
							<tr>
								<th>Контактний телефон</th>
								<td><input type="text" name="phone" <? echo $readonlystatus; ?> value="<? echo $phone; ?>"></td>
							</tr>
							<tr>
          			<th>Факс</th>
								<td><input type="text" name="fax" <? echo $readonlystatus; ?> value="<? echo $fax; ?>"></td>
          		</tr>
							<tr>
								<th>Електронна пошта</th>
								<td><input type="text" name="email" <? echo $readonlystatus; ?> value="<? echo $email; ?>"></td>
							</tr>
							<tr>
								<th>Назва банку</th>
								<td><input type="text" name="bank_name" <? echo $readonlystatus; ?> value="<? echo $bank_name; ?>"></td>
							</tr>
							<tr>
          			<th>МФО</th>
								<td><input type="text" name="mfo" <? echo $readonlystatus; ?> value="<? echo $mfo; ?>"></td>
          		</tr>
							<tr>
								<th>Рахунок</th>
								<td><input type="text" name="account" <? echo $readonlystatus; ?> value="<? echo $account; ?>"></td>
							</tr>
						</table>
					</td>
        </tr>
				<tr bgcolor="lightgrey">
					<td>Ф╕зична адреса</td>
					<td>Юридична адреса</td>
				</tr>
				<tr bgcolor="white">
          <td>
						<table border="0" width="100%">
							<tr>
								<th>╤ндекс</th>
								<td><input type="text" name="phys_zip" <? echo $readonlystatus; ?> value="<? echo $phys_zip; ?>"></td>
							</tr>
							<tr>
          			<th>Адреса(рядок 1)</th>
								<td><input type="text" name="phys_addr_1" <? echo $readonlystatus; ?> value="<? echo $phys_addr_1; ?>"></td>
          		</tr>
							<tr>
								<th>Адреса(рядок 2)</th>
								<td><input type="text" name="phys_addr_2" <? echo $readonlystatus; ?> value="<? echo $phys_addr_2; ?>"></td>
							</tr>
							<tr>
								<th>Адреса(рядок 3)</th>
								<td><input type="text" name="phys_addr_3" <? echo $readonlystatus; ?> value="<? echo $phys_addr_3; ?>"></td>
							</tr>
						</table>
					</td>
					<td>
						<table border="0" width="100%">
							<tr>
								<th>╤ндекс</th>
								<td><input type="text" name="jur_zip"  <? echo $readonlystatus; ?> value="<? echo $jur_zip; ?>"></td>
							</tr>
							<tr>
          			<th>Адреса(рядок 1)</th>
								<td><input type="text" name="jur_addr_1" <? echo $readonlystatus; ?> value="<? echo $jur_addr_1; ?>"></td>
          		</tr>
							<tr>
								<th>Адреса(рядок 2)</th>
								<td><input еype="text" name="jur_addr_2" <? echo $readonlystatus; ?> value="<? echo $jur_addr_2; ?>"></td>
							</tr>
							<tr>
								<th>Адреса(рядок 3)</th>
								<td><input type="text" name="jur_addr_3" <? echo $readonlystatus; ?> value="<? echo $jur_addr_3; ?>"></td>
							</tr>
						</table>
					</td>
        </tr>
<?
	if($readonlystatus == ""){
?>
	      <tr bgcolor="white">
          <input type="hidden" name="client_id" value="<?echo $client_id?>">
          <td colspan="2" align="center"><input type="submit" name="save" value="Зберегти"></td>
        </tr>
<?
		}
?>
      </table>
    </td>
    </form>
  </tr>
<?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
