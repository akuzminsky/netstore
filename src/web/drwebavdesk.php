<?
 $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
 readfile("$DOCUMENT_ROOT/head.html");
 include("$DOCUMENT_ROOT/netstorecore.php");
 session_start();
 if(!@session_is_registered('authdata') || $_SESSION['authdata']['permlevel'] != 'admin'){
 	header($start_url);
	}
 if($_SESSION['authdata']['permlevel'] != 'admin'){
 	header($start_url);
	}

 function avdesk_config($mysql)
 {
	 if($_SERVER["REQUEST_METHOD"] == "POST"){
	 	$drweb_avd_host = mysql_escape_string($_POST["drweb_avd_host"]);
	 	$drweb_avd_port = mysql_escape_string($_POST["drweb_avd_port"]);
	 	$drweb_avd_user = mysql_escape_string($_POST["drweb_avd_user"]);
		$drweb_avd_password = mysql_escape_string($_POST["drweb_avd_password"]);
		mysql_query("BEGIN");
		foreach(array("drweb_avd_host" => "$drweb_avd_host", 
			"drweb_avd_port" => "$drweb_avd_port",
			"drweb_avd_user" => "$drweb_avd_user",
			"drweb_avd_password" => "$drweb_avd_password") as $attribute => $value){
			if(mysql_query("UPDATE config SET value = '$value' WHERE attribute = '$attribute'") == FALSE){
				$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".mysql_error());
				mysql_close($mysql);
				exit(1);
				}
			if(mysql_affected_rows($mysql) == 0){
				if(mysql_query("INSERT INTO config(attribute, value) VALUES('$attribute', '$value')") == FALSE){
					$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
					$msg = str_replace("\n", "<br>", $msg);
					$msg = urlencode($msg);
					header ("Location: show_error.php?error=".mysql_error());
					mysql_close($mysql);
					exit(1);
					}
			}
			//echo "$attribute $value\n";
			}
		mysql_query("COMMIT");
		header("Location: ".$_SERVER["SCRIPT_NAME"]."?request=config");
		exit(0);
	 	}
	 else{
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

?>
	<form method="POST" action="<? echo $_SERVER["SCRIPT_NAME"];?>">
	<input type="hidden" name="request" value="config">
	<table>
		<tr>
		<td>AV-Desk antivirus server</td><td><input type="text" name="drweb_avd_host" value="<? echo $drweb_avd_host; ?>"></td>
		</tr>
		<tr>
		<td>AV-Desk server port</td><td><input type="text" name="drweb_avd_port" value="<? echo $drweb_avd_port; ?>"></td>
		</tr>
		<tr>
		<td>Username</td><td><input type="text" name="drweb_avd_user" value="<? echo $drweb_avd_user; ?>"></td>
		</tr>
		<tr>
		<td>Password</td><td><input type="password" name="drweb_avd_password" value="<? echo $drweb_avd_password; ?>"></td>
		</tr>
		<tr>
		<th colspan="2"><input type="submit" value="save"></th>
		</tr>
	</table>
	</form>
<?
	 }
 }

 function avdesk_group($mysql)
 {
	global $drweb_avd_host;
	global $drweb_avd_port;
	global $drweb_avd_user;
	global $drweb_avd_password;

	 if($_SERVER["REQUEST_METHOD"] == "POST"){
		 if(!empty($_POST["add"])){
			$name = mysql_escape_string($_POST["newname"]);
			$description = mysql_escape_string($_POST["newdescription"]);
			$cost = mysql_escape_string($_POST["newcost"]);
			mysql_query("BEGIN");
			$s = http_parse_message(http_get("http://".$drweb_avd_host.":".$drweb_avd_port."/avdesk/api/add-group.ds?name=".urlencode(iconv("koi8-u", "utf-8",$name))."&desc=".urlencode(iconv("koi8-u", "utf-8",$description)), array("httpauth"=> $drweb_avd_user.":".$drweb_avd_password)));
			$xmlstr = $s->body;
			$sxe = new SimpleXMLElement($xmlstr);
			$sa = $sxe->attributes();
			if($sa["rc"] == "false"){
				$msg = "Error: ".$sa["message"]." while adding group ".$name;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".$msg);
				mysql_query("ROLLBACK");
				mysql_close($mysql);
				exit(1);
				}
			$group_id = mysql_escape_string($sa["id"]);
			if(mysql_query("INSERT INTO drweb_avd_group(group_id, name, description, cost) VALUES('$group_id', '$name', '$description', '$cost')") == FALSE){
				$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".mysql_error());
				mysql_close($mysql);
				exit(1);
				}
			mysql_query("COMMIT");
			header("Location: ".$_SERVER["SCRIPT_NAME"]."?request=group");
			exit(0);
		 	}
		 if(!empty($_POST["delete"])){
			$q = "SELECT group_id FROM drweb_avd_group";
			$r = mysql_query($q);
			if($r == FALSE){
				$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".mysql_error());
				mysql_close($mysql);
				exit(1);
			}
			while($row = mysql_fetch_row($r)){
				echo $row[0]."<br>";
				echo $_POST[$row[0]];
				if($_POST[$row[0]] == "on"){
					mysql_query("BEGIN");
					if(FALSE == mysql_query("DELETE FROM drweb_avd_group WHERE group_id = '".mysql_escape_string($row[0])."'")){
						
						$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
						$msg = str_replace("\n", "<br>", $msg);
						$msg = urlencode($msg);
						header ("Location: show_error.php?error=".$msg);
						mysql_close($mysql);
						exit(1);
						}
					$s = http_parse_message(http_get("http://".$drweb_avd_host.":".$drweb_avd_port."/avdesk/api/del-group.ds?id=".urlencode($row[0]), array("httpauth"=> $drweb_avd_user.":".$drweb_avd_password)));
					$xmlstr = $s->body;
					$sxe = new SimpleXMLElement($xmlstr);
					$sa = $sxe->attributes();
					if($sa["rc"] == "false"){
						$msg = "Error: ".$sa["message"]." while deleting group ".$row[0];
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
			header("Location: ".$_SERVER["SCRIPT_NAME"]."?request=group");
			exit(0);
			}
		 if(!empty($_POST["save"])){
			$q = "SELECT group_id FROM drweb_avd_group";
			$r = mysql_query($q);
			if($r == FALSE){
				$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".mysql_error());
				mysql_close($mysql);
				exit(1);
			}
			while($row = mysql_fetch_row($r)){
				mysql_query("BEGIN");
				echo $row[0]."<br>";
				echo $_POST[$row[0]];
				$name = mysql_escape_string($_POST["name-".$row[0]]);
				$description = mysql_escape_string($_POST["description-".$row[0]]);
				$cost = mysql_escape_string($_POST["cost-".$row[0]]);
				if(FALSE == mysql_query("UPDATE drweb_avd_group SET name = '$name', 
						description = '$description', 
						cost = '$cost' WHERE group_id = '".mysql_escape_string($row[0])."'")){
					
					$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
					$msg = str_replace("\n", "<br>", $msg);
					$msg = urlencode($msg);
					header ("Location: show_error.php?error=".$msg);
					mysql_close($mysql);
					exit(1);
					}
				mysql_query("COMMIT");
				}
			header("Location: ".$_SERVER["SCRIPT_NAME"]."?request=group");
			exit(0);
			}
	 	}
	 else{
		$q = "SELECT group_id, name, description, cost FROM drweb_avd_group";
		if(($r = mysql_query($q)) == FALSE){
			$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".mysql_error());
			mysql_close($mysql);
			exit(1);
			}
?>
	<form method="POST" action="<? echo $_SERVER["SCRIPT_NAME"];?>">
	<input type="hidden" name="request" value="group">
	<table>
		<tr>
			<th>&nbsp;</th><th>Група</th><th>Опис</th><th>Варт╕сть без ПДВ, грн.</th>
		</tr>
<?
	while($row = mysql_fetch_row($r)){
		echo "<tr><td><input type=\"checkbox\" name=\"".$row[0]."\"></td>
			<td><input type=\"text\" name=\"name-".$row[0]."\" value=\"".$row[1]."\"></td>
			<td><input type=\"text\" name=\"description-".$row[0]."\" value=\"".$row[2]."\"></td>
			<td><input type=\"text\" name=\"cost-".$row[0]."\" value=\"".$row[3]."\"></td>
			</tr>";
		}
?>
		<tr>
			<td></td><td><input type="text" name="newname" value=""></td><td><input type="text" name="newdescription" value=""></td><td><input type="text" name="newcost" value=""></td>
		</tr>
		<tr>
			<td></td><td><input type="submit" name="delete" value="Delete selected"></td><td><input type="submit" name="add" value="Add a group"></td><td><input type="submit" name="save" value="Save"></td>
		</tr>
	</table>
	</form>
<?
	 }
 }

 function avdesk_subscriber($mysql)
 {
	global $drweb_avd_host;
	global $drweb_avd_port;
	global $drweb_avd_user;
	global $drweb_avd_password;

	 if($_SERVER["REQUEST_METHOD"] == "POST"){
		 if(!empty($_POST["add"])){
			$newclient_id= mysql_escape_string($_POST["newclient_id"]);
			$newpassword = mysql_escape_string($_POST["newpassword"]);
			$newgroup = mysql_escape_string($_POST["newgroup"]);
			$newdescription = mysql_escape_string($_POST["newdescription"]);

			$newaccessd1 = mysql_escape_string($_POST["newaccessd1"]);
			$newaccessm1 = mysql_escape_string($_POST["newaccessm1"]);
			$newaccessy1 = mysql_escape_string($_POST["newaccessy1"]);
			
			$newfromd1 = mysql_escape_string($_POST["newfromd1"]);
			$newfromm1 = mysql_escape_string($_POST["newfromm1"]);
			$newfromy1 = mysql_escape_string($_POST["newfromy1"]);
			
			$newtilld1 = mysql_escape_string($_POST["newtilld1"]);
			$newtillm1 = mysql_escape_string($_POST["newtillm1"]);
			$newtilly1 = mysql_escape_string($_POST["newtilly1"]);
			
			mysql_query("BEGIN");
			if($newaccessd1 == 0 || $newaccessm1 == 0 || $newaccessy1 == 0){
				$access = "";
				$newaccessd1 = $newaccessm1 = $newaccessy1 = 0;
				}
			else{
				$access = "&year=".urlencode($newaccessy1);
				$access .= "&month=".urlencode($newaccessm1);
				$access .= "&day=".urlencode($newaccessd1);
				}
			if($newfromd1 == 0 || $newfromm1 == 0 || $newfromy1 == 0){
				$from = "";
				$newfromd1 = $newfromm1 = $newfromy1 = 0;
				}
			else{
				$from = "&fromyear=".urlencode($newfromy1);
				$from .= "&frommonth=".urlencode($newfromm1);
				$from .= "&fromday=".urlencode($newfromd1);
				}
			if($newtilld1 == 0 || $newtillm1 == 0 || $newtilly1 == 0){
				$till = "";
				$newtilld1 = $newtillm1 = $newtilly1 = 0;
				}
			else{
				$till = "&tillyear=".urlencode($newtilly1);
				$till .= "&tillmonth=".urlencode($newtillm1);
				$till .= "&tillday=".urlencode($newtilld1);
				}
			$s = http_parse_message(http_get("http://".$drweb_avd_host.":".$drweb_avd_port."/avdesk/api/add-customer.ds?&pwd=".urlencode(iconv("koi8-u", "utf-8", $newpassword))."&pgrp=".urlencode($newgroup)."&desc=".urlencode(iconv("koi8-u", "utf-8", $newdescription)).$access.$from.$till, array("httpauth"=> $drweb_avd_user.":".$drweb_avd_password)));
			$xmlstr = $s->body;
			//echo "$xmlstr"; exit(0);
			$sxe = new SimpleXMLElement($xmlstr);
			$sa = $sxe->attributes();
			if($sa["rc"] == "false"){
				$msg = "Error: ".$sa["message"]." while adding subscriber ".$newdescription;
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
			$q = "INSERT INTO drweb_avd_customer
				(id, client_id, pwd, pgrp, `desc`, access_date, from_block_date, till_block_date, url) 
				VALUES('$id', '$newclient_id', '$newpassword', '$newgroup', '$newdescription', 
				'$newaccessy1-$newaccessm1-$newaccessd1', 
				'$newfromy1-$newfromm1-$newfromd1', 
				'$newtilly1-$newtillm1-$newtilld1', 
				'$url')";
			if(mysql_query($q) == FALSE){
				$msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".$msg);
				mysql_close($mysql);
				exit(1);
				}
			mysql_query("COMMIT");
			header("Location: ".$_SERVER["SCRIPT_NAME"]."?request=subscriber");
			exit(0);
		 	}
		 if(!empty($_POST["delete"])){
			$q = "SELECT id FROM drweb_avd_customer";
			$r = mysql_query($q);
			if($r == FALSE){
				$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".mysql_error());
				mysql_close($mysql);
				exit(1);
			}
			while($row = mysql_fetch_row($r)){
				if($_POST[$row[0]] == "on"){
					mysql_query("BEGIN");
					if(FALSE == mysql_query("DELETE FROM drweb_avd_customer WHERE id = '".mysql_escape_string($row[0])."'")){
						
						$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
						$msg = str_replace("\n", "<br>", $msg);
						$msg = urlencode($msg);
						header ("Location: show_error.php?error=".$msg);
						mysql_close($mysql);
						exit(1);
						}
					$xmlurl = "http://".$drweb_avd_host.":".$drweb_avd_port."/avdesk/api/del-customer.ds?id=".urlencode($row[0]);
					$s = http_parse_message(http_get($xmlurl, 
						array("httpauth"=> $drweb_avd_user.":".$drweb_avd_password)));
					$xmlstr = $s->body;
					$sxe = new SimpleXMLElement($xmlstr);
					$sa = $sxe->attributes();
					if($sa["rc"] == "false"){
						$msg = "Error: ".$sa["message"]." while deleting customer ".$row[0];
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
			header("Location: ".$_SERVER["SCRIPT_NAME"]."?request=subscriber");
			exit(0);
			}
		 if(!empty($_POST["save"])){
			$q = "SELECT id FROM drweb_avd_customer";
			$r = mysql_query($q);
			if($r == FALSE){
				$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".mysql_error());
				mysql_close($mysql);
				exit(1);
			}
			while($row = mysql_fetch_row($r)){
				mysql_query("BEGIN");
				$id = $row[0];
				$newpassword = mysql_escape_string($_POST["password-".$id]);
				$newdescription = mysql_escape_string($_POST["description-".$id]);
				$accessd1 = mysql_escape_string($_POST["access-".$id."d1"]);
				$accessm1 = mysql_escape_string($_POST["access-".$id."m1"]);
				$accessy1 = mysql_escape_string($_POST["access-".$id."y1"]);
				$fromd1 = mysql_escape_string($_POST["from-".$id."d1"]);
				$fromm1 = mysql_escape_string($_POST["from-".$id."m1"]);
				$fromy1 = mysql_escape_string($_POST["from-".$id."y1"]);
				$tilld1 = mysql_escape_string($_POST["till-".$id."d1"]);
				$tillm1 = mysql_escape_string($_POST["till-".$id."m1"]);
				$tilly1 = mysql_escape_string($_POST["till-".$id."y1"]);
				if(FALSE == mysql_query("UPDATE drweb_avd_customer 
					SET pwd = '$newpassword',
					`desc` = '$newdescription',
					`access_date` = '$accessy1-$accessm1-$accessd1',
					`from_block_date` = '$fromy1-$fromm1-$fromd1',
					`till_block_date` = '$tilly1-$tillm1-$tilld1'
				       	WHERE id = '".mysql_escape_string($row[0])."'")){
					
					$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
					$msg = str_replace("\n", "<br>", $msg);
					$msg = urlencode($msg);
					mysql_query("ROLLBACK");
					header ("Location: show_error.php?error=".$msg);
					mysql_close($mysql);
					exit(1);
					}
				// Set access date
				if($accessd1 == 0 || $accessm1 == 0 || $accessy1 == 0){
					$access = "";
					}
				else{
					$access = "&year=".urlencode($accessy1)."&month=".urlencode($accessm1)."&day=".urlencode($accessd1);
					}
				$xml_url = "http://".$drweb_avd_host.":".$drweb_avd_port."/avdesk/api/set-expiration.ds?id=".urlencode($id).$access;
				echo "$xml_url\n";
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
				// Set blocking date
				if($fromd1 == 0 || $fromm1 == 0 || $fromy1 == 0){
					$from = "";
					}
				else{
					$from = "&fromyear=".urlencode($fromy1)."&frommonth=".urlencode($fromm1)."&fromday=".urlencode($fromd1);
					}
				if($tilld1 == 0 || $tillm1 == 0 || $tilly1 == 0){
					$till = "";
					}
				else{
					$till = "&tillyear=".urlencode($tilly1)."&tillmonth=".urlencode($tillm1)."&tillday=".urlencode($tilld1);
					}
				$xml_url = "http://".$drweb_avd_host.":".$drweb_avd_port."/avdesk/api/set-blocking.ds?id=".urlencode($id).$from.$till;
				echo "$xml_url\n";
				$s = http_parse_message(http_get($xml_url, 
					array("httpauth"=> $drweb_avd_user.":".$drweb_avd_password)));
				$xmlstr = $s->body;
				echo "$xmlstr\n";
				$sxe = new SimpleXMLElement($xmlstr);
				$sa = $sxe->attributes();
				if($sa["rc"] == "false"){
					$msg = "Error: ".$sa["message"]." while setting blocking date for ".$id;
					$msg = str_replace("\n", "<br>", $msg);
					$msg = urlencode($msg);
					header ("Location: show_error.php?error=".$msg);
					mysql_query("ROLLBACK");
					mysql_close($mysql);
					exit(1);
					}
				mysql_query("COMMIT");
				}
			header("Location: ".$_SERVER["SCRIPT_NAME"]."?request=subscriber");
			exit(0);
			}
	 	}
	 else{
		 $q = "SELECT drweb_avd_customer.id,
			 client.description,
			 drweb_avd_customer.pwd, 
			 drweb_avd_group.name,
			 drweb_avd_customer.desc, 
			 YEAR(drweb_avd_customer.access_date), 
			 MONTH(drweb_avd_customer.access_date), 
			 DAY(drweb_avd_customer.access_date), 
			 YEAR(drweb_avd_customer.from_block_date),
			 MONTH(drweb_avd_customer.from_block_date),
			 DAY(drweb_avd_customer.from_block_date),
			 YEAR(drweb_avd_customer.till_block_date),
			 MONTH(drweb_avd_customer.till_block_date),
			 DAY(drweb_avd_customer.till_block_date),
			 drweb_avd_customer.url
			 FROM drweb_avd_customer
			 LEFT JOIN drweb_avd_group ON drweb_avd_customer.pgrp = drweb_avd_group.group_id
			 LEFT JOIN client ON drweb_avd_customer.client_id = client.id";
		if(($r = mysql_query($q)) == FALSE){
			$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".mysql_error());
			mysql_close($mysql);
			exit(1);
			}
?>
	<form method="POST" action="<? echo $_SERVER["SCRIPT_NAME"];?>">
	<input type="hidden" name="request" value="subscriber">
	<table>
		<tr>
			<th>&nbsp;</th>
			<th>Абонент / DrWEB id</th>
			<th>Пароль</th>
			<th>Група</th>
			<th>Опис</th>
			<th>Дата зак╕нчення допуску /
			Початок блокування /
			К╕нець блокування</th>
		</tr>
<?
		 while($row = mysql_fetch_row($r)){
?>
	<tr><td><input type="checkbox" name="<? echo $row[0]; ?>"></td>
		<td><table>
		<tr><td><? echo $row[1]; ?></td></tr>
		<tr><td><a href="<? echo $row[14]; ?>"><? echo $row[0]; ?></a></td></tr>
		</table></td>
		<td><input type="text" name="password-<? echo $row[0]; ?>" value="<? echo $row[2]; ?>"></td>
		<td><? echo $row[3]; ?></td>
		<td><input type="text" name="description-<? echo $row[0]; ?>" value="<? echo $row[4]; ?>"></td>
		<td><table>
		<tr><td><? echo showdate("access-".$row[0], array("mday" => $row[7], "mon" => $row[6], "year" => $row[5])); ?></td></tr>
		<tr><td><? echo showdate("from-".$row[0], array("mday" => $row[10], "mon" => $row[9], "year" => $row[8])); ?></td></tr>
		<tr><td><? echo showdate("till-".$row[0], array("mday" => $row[13], "mon" => $row[12], "year" => $row[11])); ?></td></tr>
		</table></td>
<?
		}
?>
		<tr>
			<td>
			</td>
			<td><select name="newclient_id">
<?
	$q = "SELECT id, description FROM client;";
	if(($r = mysql_query($q)) == FALSE){
		$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".mysql_error());
		mysql_close($mysql);
		exit(1);
		}
	while($row = mysql_fetch_row($r)){
		echo "<option value=\"".$row[0]."\">".$row[1]."</option>\n";
		}
?>
			</select></td>
			<td><input type="text" name="newpassword" value="">
			<td><select name="newgroup">
<?
	$q = "SELECT group_id, name FROM drweb_avd_group";
	if(($r = mysql_query($q)) == FALSE){
		$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".mysql_error());
		mysql_close($mysql);
		exit(1);
		}
	while($row = mysql_fetch_row($r)){
		echo "<option value=\"".$row[0]."\">".$row[1]."</option>\n";
		}
?>
			</select></td>
			<td><input type="text" name="newdescription"></td>
			<td><table>
			<tr><td><? showdate("newaccess", array("mday" => 0, "mon" => 0, "year" => 0))?></td></tr>
			<tr><td><? showdate("newfrom", array("mday" => 0, "mon" => 0, "year" => 0))?></td></tr>
			<tr><td><? showdate("newtill", array("mday" => 0, "mon" => 0, "year" => 0))?></td></tr>
			</table></td>
		</tr>
		<tr>
			<td></td>
			<td><input type="submit" name="delete" value="Delete selected"></td>
			<td><input type="submit" name="add" value="Add a subscriber"></td>
			<td><input type="submit" name="save" value="Save"></td>
		</tr>
	</table>
	</form>
<?
	 }
 }
function avdesk_statistic($mysql)
{
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
 mysql_set_charset("koi8u");
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
 $request = $_SERVER["REQUEST_METHOD"] == "POST" ? $_POST["request"] : $_GET["request"];
 ?>
 <tr>
	<td valign="top" width="20%"><? include("left_admin.php"); ?></td>
	<td>
		<table>
			<tr>
				<td valign="top">
					<table>
						<tr><td valign="top"><a href="<? echo $_SERVER["SCRIPT_NAME"]; ?>?request=config">Конф╕гурац╕я</a></td></tr>
						<tr><td><a href="<? echo $_SERVER["SCRIPT_NAME"]; ?>?request=group">Групи</a></td></tr>
						<tr><td><a href="<? echo $_SERVER["SCRIPT_NAME"]; ?>?request=subscriber">Абоненти</a></td></tr>
						<tr><td><a href="<? echo $_SERVER["SCRIPT_NAME"]; ?>?request=statistic">Статистика</a></td></tr>
					</table>
				</td>
				<td valign="top">
<? 
 switch($request){
	 case "config": avdesk_config($mysql); break;
	 case "group": avdesk_group($mysql); break;
	 case "subscriber": avdesk_subscriber($mysql); break;
	 case "statistic": avdesk_statistic($mysql); break;
	 default: echo "&nbsp;";
 }
?>

</td>
			</tr>
		</table>
<?
 /*
 echo "<pre>";
 	$s = http_parse_message(http_get("http://192.168.127.129:9080/avdesk/api/srv-statistics.ds", array("httpauth"=>"admin:Faveriba")));
 	$xmlstr = $s->body;
 	echo "$xmlstr";
	$sxe = new SimpleXMLElement($xmlstr);

	echo $sxe->getName() . "\n";

	foreach ($sxe->children() as $child)
	{
		    echo $child->getName() . "\n";
	}
	foreach($sxe->attributes() as $a => $b) {
		    echo $a,'="',$b,"\"\n";
	}
	$sa = $sxe->attributes();
	print_r($sa);
	echo "API:".$sa["API"];
  */
?>
	</td>
 </tr>
 <?
 mysql_close($mysql);
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
