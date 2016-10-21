<?
 $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
 readfile("$DOCUMENT_ROOT/head.html");
 session_start();
 if(!@session_is_registered('authdata') || $_SESSION['authdata']['permlevel'] != 'admin'){
 	header($start_url);
	}
 if($_SESSION['authdata']['permlevel'] != 'admin'){
 	header($start_url);
	}
 
 $authdata = $_SESSION['authdata'];
 include("$DOCUMENT_ROOT/netstorecore.php");
 include("top.php");
 $login = $authdata['login'];
 $passwd = $authdata['passwd'];
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
?>
 <form action="<? echo $_SERVER['PHP_SELF']; ?>" method=POST>
 <tr><td valign=top align=center>
 	<table border=0 valign=top>
 	<tr>
		<td>
			<table>
				<tr>
 	    		<th align=right>Назва ф╕льтра:</th>
 	    		<td><input type='text' name='description' size=64 value='<?echo $_POST["description"]?>'></td>
				</tr>
				<tr>
 	    		<th align=right>Початок д╕╖ фильтра(YYYY-MM-DD HH:mm:ss):</th>
 	    		<td><input type='text' name='starttimestamp' size=64 value='<?echo $_POST["starttimestamp"]?>'></td>
				</tr>
				<tr>
 	    		<th align=right>К╕нець д╕╖ фильтра(YYYY-MM-DD HH:mm:ss):</th>
 	    		<td><input type='text' name='stoptimestamp' size=64 value='<?echo $_POST["stoptimestamp"]?>'></td>
				</tr>
				<tr>
 	    		<th align=right><input type='checkbox' name='refilter'></th>
 	    		<td>Перерахувати траф╕к по цьому ф╕льтру</td>
				</tr>
			</table>
		</td>
	</tr>
 	<tr>
 	    <td colspan=2>
			 	<table align=center cellspacing="1" cellpadding="2" border="0" bgcolor=silver width=100%>
					<tr bgcolor=lightgreen>
						<td>Протокол</td>
						<td>Часовий ╕нтревал</td>
						<td>╤нтерфейс</td>
						<td>Мережа</td>
						<td>Порт</td>
						<td>AS</td>
					</tr>
					<?
					$num = empty($_POST) ? 1 : $_POST["num"];
					$num = !empty($_POST["add_row"]) ? $num + 1 : $num;
					$num = !empty($_POST["del_row"]) ? $num - 1 : $num;
					for($i = 0; $i < $num; $i++){
					?>
					<tr bgcolor=white>
						<td><input type='text' name='protocol<? echo $i?>' size=8 value='<?echo $_POST["protocol".$i]?>'></td>
						<td><input type='text' name='timerange<? echo $i?>' size=16 value='<?echo $_POST["timerange".$i]?>'></td>
						<td><table><tr>
						<td>Входящий</td>
						<td><input type='text' name='if_id<? echo $i?>0' size=8 value='<?echo $_POST["if_id".$i."0"]?>'></td>
						</tr>
						<tr>
						<td>Исходящий</td>
						<td><input type='text' name='if_id<? echo $i?>1' size=8 value='<?echo $_POST["if_id".$i."1"]?>'></td>
						</tr></table></td>
						<td><table><tr>
						<td>Источник</td>
						<td><input type='text' name='network<? echo $i?>0' size=16 value='<?echo $_POST["network".$i."0"]?>'></td>
						<td><input type='text' name='netmask<? echo $i?>0' size=16 value='<?echo $_POST["netmask".$i."0"]?>'></td>
						</tr>
						<tr>
						<td>Получатель</td>
						<td><input type='text' name='network<? echo $i?>1' size=16 value='<?echo $_POST["network".$i."1"]?>'></td>
						<td><input type='text' name='netmask<? echo $i?>1' size=16 value='<?echo $_POST["netmask".$i."1"]?>'></td>
						</tr></table></td>
						<td><table><tr>
						<td>Источник</td>
						<td><input type='text' name='port<? echo $i?>0' size=8 value='<?echo $_POST["port".$i."0"]?>'></td>
						</tr>
						<tr>
						<td>Получатель</td>
						<td><input type='text' name='port<? echo $i?>1' size=8 value='<?echo $_POST["port".$i."1"]?>'></td>
						</tr></table></td>
						<td><table><tr>
						<td>Источник</td>
						<td><input type='text' name='as<? echo $i?>0' size=8 value='<?echo $_POST["as".$i."0"]?>'></td>
						</tr>
						<tr>
						<td>Получатель</td>
						<td><input type='text' name='as<? echo $i?>1' size=8 value='<?echo $_POST["as".$i."1"]?>'></td>
						</tr></table></td>
					</tr>
					<?
						}
					?>
						<input type='hidden' name='num' value='<?echo $num?>'>
						<input type='hidden' name="client_id" value='<?echo empty($_POST["client_id"]) ? $_GET["client_id"] : $_POST["client_id"];?>'>
				</table>
	     </td>
	 </tr>
   <tr>
 		<td colspan=2><table><tr>
		<td><input type="submit" name="add_row" value="Добавить условие">
					     <td><input type="submit" name="del_row" value="Удалить условие">
					     <td><input type="submit" name="save" value="Сохранить фильтр">
						    </tr></tr></table>
	</tr>

	 <tr>
	 <?
	 $client_id = empty($_POST["client_id"]) ? $_GET["client_id"] : $_POST["client_id"];
	 ?>
	 <td colspan=2><a href="<?echo "show_client.php?client_id=".$client_id; ?>">Вернуться</a>
	 </tr>
	 </table>
	 </td>
 </form>
 <?
 if(!empty($_POST["save"])){
 	mysql_query("BEGIN");
	$client_id = mysql_escape_string($_POST['client_id']);
	$starttimestamp = mysql_escape_string($_POST['starttimestamp']);
	$stoptimestamp = mysql_escape_string($_POST['stoptimestamp']);
	$description = mysql_escape_string($_POST['description']);
 	$query = "INSERT INTO filter(client_id, starttimestamp, stoptimestamp, description) 
			values($client_id, '$starttimestamp', '$stoptimestamp', '$description')";
	if(FALSE == mysql_query($query)){
		header ("Location: show_error.php?error=".mysql_error());
		mysql_query("ROLLBACK");
		mysql_close($mysql);
		exit(-1);
		}
	$filter_id = mysql_insert_id();
	for($i = 0; $i < $_POST["num"]; $i++){
		$timerange = $_POST["timerange".$i];
		$proto = !empty($_POST["protocol".$i]) ? $_POST["protocol".$i] : 0;
		$in_if = !empty($_POST["if_id".$i."0"]) ? $_POST["if_id".$i."0"] : 0;
		$out_if = !empty($_POST["if_id".$i."1"]) ? $_POST["if_id".$i."1"] : 0;
		$src_addr = !empty($_POST["network".$i."0"]) ? $_POST["network".$i."0"] : 0;
		$src_mask = !empty($_POST["netmask".$i."0"]) ? $_POST["netmask".$i."0"] : 0;
		$dst_addr = !empty($_POST["network".$i."1"]) ? $_POST["network".$i."1"] : 0;
		$dst_mask = !empty($_POST["netmask".$i."1"]) ? $_POST["netmask".$i."1"] : 0;
		$src_port = !empty($_POST["port".$i."0"]) ? $_POST["port".$i."0"] : 0;
		$dst_port = !empty($_POST["port".$i."1"]) ? $_POST["port".$i."1"] : 0;
		$src_as = !empty($_POST["as".$i."0"]) ? $_POST["as".$i."0"] : 0;
		$dst_as = !empty($_POST["as".$i."1"]) ? $_POST["as".$i."1"] : 0;
		
		$timerange = mysql_escape_string($timerange);
		$proto = mysql_escape_string($proto);
		$in_if = mysql_escape_string($in_if);
		$out_if = mysql_escape_string($out_if);
		$src_addr = mysql_escape_string($src_addr);
		$src_mask = mysql_escape_string($src_mask);
		$dst_addr = mysql_escape_string($dst_addr);
		$dst_mask = mysql_escape_string($dst_mask);
		$src_port = mysql_escape_string($src_port);
		$dst_port = mysql_escape_string($dst_port);
		$src_as = mysql_escape_string($src_as);
		$dst_as = mysql_escape_string($dst_as);
		$query = "INSERT INTO filter_definition(
			filter_id, 
			timerange,
			proto,
			in_if,
			out_if,
			src_addr,
			src_mask,
			dst_addr,
			dst_mask,
			src_port,
			dst_port,
			src_as,
			dst_as) VALUES(
			$filter_id,
			'$timerange',
			$proto,
			$in_if,
			$out_if,
			INET_ATON('$src_addr'),
			INET_ATON('$src_mask'),
			INET_ATON('$dst_addr'),
			INET_ATON('$dst_mask'),
			$src_port,
			$dst_port,
			$src_as,
			$dst_as)";
		if(FALSE == mysql_query($query)){
			header ("Location: show_error.php?error=".mysql_error());
			mysql_query("ROLLBACK");
			mysql_close($mysql);
			exit(-1);
			}
		}
	mysql_query("COMMIT");
	if(!empty($_POST["refilter"])){
		$cmd = $bin_dir."/refilter -u ".$login." -p ".$passwd." -h ".$host." -d ".$db." -acb -r ".$filter_id;
		session_write_close();
		exec($cmd);
		session_start();
		}
	header("Location: show_client.php?client_id=".$client_id);
	exit(0);
	}
 ?>
 </tr>
<?


 mysql_close($mysql);
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
