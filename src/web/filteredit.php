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
		session_timeout();
		}
	if(FALSE == mysql_select_db($db)){
		$msg = mysql_error();
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		mysql_close($mysql);
		exit(1);
		}
	$REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
	if($REQUEST_METHOD == "GET"){
		$filter_id = mysql_escape_string($_GET["filter_id"]);
		$client_id = mysql_escape_string($_GET["client_id"]);
		}
	if($REQUEST_METHOD == "POST"){
		$filter_id = mysql_escape_string($_POST["filter_id"]);
		$client_id = mysql_escape_string($_POST["client_id"]);
		}
	if(!empty($_POST["add_row"])){
		mysql_query("BEGIN");
		$query = "INSERT INTO filter_definition(filter_id) VALUES('$filter_id')";
		if(FALSE == mysql_query($query)){
			$msg = "Error: ".mysql_error()." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
			mysql_close($mysql);
			exit(1);
			}
		mysql_query("COMMIT");
		}
	if(!empty($_POST["del_row"])){
		mysql_query("BEGIN");
		$query = "SELECT MAX(id) AS max_id 
				FROM filter_definition 
				WHERE filter_id = '$filter_id'
				HAVING max_id IS NOT NULL";
		$res = mysql_query($query);
		if(FALSE == $res){
			$msg = "Error: ".mysql_error()." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
			mysql_close($mysql);
			exit(1);
			}
		if(mysql_num_rows($res) == 1){
			$max_id = mysql_result($res, 0, "max_id");
			}
		else{
			$msg = "Ф╕льтр номер $filter_id не ╕сну╓ або не ма╓ жодно╖ умови.";
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
			mysql_close($mysql);
			exit(1);
			}
		$query = "DELETE FROM filter_definition WHERE id = '$max_id'";
		if(FALSE == mysql_query($query)){
			$msg = "Error: ".mysql_error()." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
			mysql_close($mysql);
			exit(1);
			}
		mysql_query("COMMIT");
		}
	if(!empty($_POST["save"])){
		mysql_query("BEGIN");
		$description = mysql_escape_string($_POST["description"]);
		$starttimestamp = mysql_escape_string($_POST["starttimestamp"]);
		$stoptimestamp = mysql_escape_string($_POST["stoptimestamp"]);
		$inverse = (mysql_escape_string($_POST["inverse"]) == "on") ? 1 : 0;
		$query = "UPDATE filter SET description = '$description', 
				starttimestamp = '$starttimestamp', 
				stoptimestamp = '$stoptimestamp',
				inverse = '$inverse'
				WHERE id = '$filter_id'";
		if(FALSE == mysql_query($query)){
			$msg = "Error: ".mysql_error()." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
			mysql_close($mysql);
			exit(1);
			}
		for($i = 0; $i < $_POST["num"]; $i++){
			$def_id = mysql_escape_string($_POST["def_id".$i]);
			$timerange = mysql_escape_string($_POST["timerange".$i]);
			$proto = !empty($_POST["proto".$i]) ? mysql_escape_string($_POST["proto".$i]) : 0;
			$in_if = !empty($_POST["in_if".$i]) ? mysql_escape_string($_POST["in_if".$i]) : 0;
			$out_if = !empty($_POST["out_if".$i]) ? mysql_escape_string($_POST["out_if".$i]) : 0;
			$src_addr = !empty($_POST["src_addr".$i]) ? mysql_escape_string($_POST["src_addr".$i]) : 0;
			$src_mask = !empty($_POST["src_mask".$i]) ? mysql_escape_string($_POST["src_mask".$i]) : 0;
			$dst_addr = !empty($_POST["dst_addr".$i]) ? mysql_escape_string($_POST["dst_addr".$i]) : 0;
			$dst_mask = !empty($_POST["dst_mask".$i]) ? mysql_escape_string($_POST["dst_mask".$i]) : 0;
			$src_port = !empty($_POST["src_port".$i]) ? mysql_escape_string($_POST["src_port".$i]) : 0;
			$dst_port = !empty($_POST["dst_port".$i]) ? mysql_escape_string($_POST["dst_port".$i]) : 0;
			$src_as = !empty($_POST["src_as".$i]) ? mysql_escape_string($_POST["src_as".$i]) : 0;
			$dst_as = !empty($_POST["dst_as".$i]) ? mysql_escape_string($_POST["dst_as".$i]) : 0;
		
			$query = "UPDATE filter_definition
					SET filter_id = '$filter_id',
					timerange = '$timerange',
					proto = '$proto',
					in_if = '$in_if',
					out_if = '$out_if',
					src_addr = INET_ATON('$src_addr'),
					src_mask = INET_ATON('$src_mask'),
					dst_addr = INET_ATON('$dst_addr'),
					dst_mask = INET_ATON('$dst_mask'),
					src_port = '$src_port',
					dst_port = '$dst_port',
					src_as = '$src_as',
					dst_as = '$dst_as'
					WHERE id = '$def_id'";
			if(FALSE == mysql_query($query)){
				$msg = "Error: ".mysql_error()." while executing:\n".$query;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".$msg);
				mysql_query("ROLLBACK");
				mysql_close($mysql);
				exit(1);
				}
			}
		mysql_query("COMMIT");
		if(!empty($_POST['refilter'])){
			$cmd = $bin_dir."/refilter -u ".$login." -p ".$passwd." -h ".$host." -d ".$db." -acb -r ".$filter_id." -e ".$T3;
			session_write_close();
			exec($cmd);
			session_start();
			}
		}
?>
	<form action="<? echo $_SERVER['PHP_SELF']; ?>" method="POST">
		<tr>
			<td valign="top" align="center">
 				<table border="0" valign="top">
 					<tr>
						<td>
							<table>
								<tr>
 	    						<th align="right">Назва ф╕льтра:</th>
<?
			$query = "SELECT description, starttimestamp, stoptimestamp, inverse
					FROM filter 
					WHERE id = '$filter_id'";
			$res = mysql_query($query);
			if(FALSE == $res){
				$msg = "Error: ".mysql_error()." while executing:\n".$query;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".$msg);
				mysql_close($mysql);
				exit(1);
				}
			if(0 == mysql_num_rows($res)){
				$msg = "Ф╕льтер номер $filter_id не ╕сну╓";
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".$msg);
				mysql_close($mysql);
				exit(1);
				}
			$description = mysql_result($res, 0, "description");
			$starttimestamp = mysql_result($res, 0, "starttimestamp");
			$stoptimestamp = mysql_result($res, 0, "stoptimestamp");
			$inverse = mysql_result($res, 0, "inverse");
?>
 	    						<td><input type="text" name="description" size="64" value="<?echo $description?>"></td>
								</tr>
								<tr>
									<th align="right">Початок д╕╖ ф╕льтра(YYYY-MM-DD HH:mm:ss):</th>
									<td><input type="text" name="starttimestamp" size="64" value="<?echo $starttimestamp?>"></td>
								</tr>
								<tr>
									<th align="right">К╕нець д╕╖ ф╕льтра(YYYY-MM-DD HH:mm:ss):</th>
									<td><input type="text" name="stoptimestamp" size="64" value="<?echo $stoptimestamp?>"></td>
								</tr>
								<tr>
									<td align="right"><input type="checkbox" name="refilter"></th>
									<td>Перерахувати траф╕к по цьому ф╕льтру</td>
								</tr>
								<tr>
									<td align="right"><input type="checkbox" name="inverse" <? if($inverse == 1) echo "checked"; ?>></th>
									<td>Враховувати т╕льки траф╕к, що <b>не задовольня╓</b> умовам ф╕льтру</td>
								</tr>
							</table>
						</td>
					</tr>
 					<tr>
 	    			<td colspan="2">
							<script language="JavaScript" type="text/javascript">
function addRowToTable()
{
	var tbl = document.getElementById("fdef");
	var lastRow = tbl.rows.length;
	// if there's no header row in the table, then iteration = lastRow + 1
	var iteration = lastRow;
  var fdefrow = tbl.insertRow(lastRow);
	fdefrow.style.backgroundColor = "white";
	fdefrow.id = 'row' + iteration;
	// Add protocol field
	var cell = fdefrow.insertCell(0);
	var el = document.createElement('input');
	el.setAttribute("type", "text");
	el.setAttribute("name", "proto"	+ iteration);
	el.setAttribute("size", "8");
	el.setAttribute("value", "0");
	cell.appendChild(el);	
	
	// Add timerange field
	cell = fdefrow.insertCell(1);
	el = document.createElement('input');
	el.setAttribute("type", "text");
	el.setAttribute("name", "timerange"	+ iteration);
	el.setAttribute("size", "16");
	el.setAttribute("value", "");
	cell.appendChild(el);	
	
	// Add interfaces table
	// first create TABLE
	cell = fdefrow.insertCell(2);
	el = document.createElement("table");
	el.setAttribute("id", "if" + iteration);
	cell.appendChild(el);
	// Insert row
	tbl = document.getElementById("if" + iteration);
	lastRow = tbl.rows.length;
	row = tbl.insertRow(lastRow);
	// Insert cell "Вх╕дний"
	cell = row.insertCell(0);
	textNode = document.createTextNode("Вх╕дний");
	cell.appendChild(textNode);
	// Insert input field	
	cell = row.insertCell(1);
	el = document.createElement("input");
	el.setAttribute("type", "text");
	el.setAttribute("name", "in_if" + iteration);
	el.setAttribute("size", "8");
	el.setAttribute("value", "0");
	cell.appendChild(el);
	// Insert second row
	row = tbl.insertRow(lastRow + 1);
	// Insert cell "Вих╕дний"
	cell = row.insertCell(0);
	textNode = document.createTextNode("Вих╕дний");
	cell.appendChild(textNode);
	// Insert input field	
	cell = row.insertCell(1);
	el = document.createElement("input");
	el.setAttribute("type", "text");
	el.setAttribute("name", "out_if" + iteration);
	el.setAttribute("size", "8");
	el.setAttribute("value", "0");
	cell.appendChild(el);
	// end of interfaces
		
	// Add IP address table
	// first create TABLE
	cell = fdefrow.insertCell(3);
	el = document.createElement("table");
	el.setAttribute("id", "addr" + iteration);
	cell.appendChild(el);
	// Insert row
	tbl = document.getElementById("addr" + iteration);
	lastRow = tbl.rows.length;
	row = tbl.insertRow(lastRow);
	// Insert cell "Джерело"
	cell = row.insertCell(0);
	var textNode = document.createTextNode("Джерело");
	cell.appendChild(textNode);
	// Insert input field	for address
	cell = row.insertCell(1);
	el = document.createElement("input");
	el.setAttribute("type", "text");
	el.setAttribute("name", "src_addr" + iteration);
	el.setAttribute("size", "16");
	el.setAttribute("value", "0.0.0.0");
	cell.appendChild(el);
	// Insert input field for netmask
	cell = row.insertCell(2);
	el = document.createElement("input");
	el.setAttribute("type", "text");
	el.setAttribute("name", "src_mask" + iteration);
	el.setAttribute("size", "16");
	el.setAttribute("value", "0.0.0.0");
	cell.appendChild(el);
	// Insert second row
	row = tbl.insertRow(lastRow + 1);
	// Insert cell "Отримувач"
	cell = row.insertCell(0);
	textNode = document.createTextNode("Отримувач");
	cell.appendChild(textNode);
	// Insert input field	for address
	cell = row.insertCell(1);
	el = document.createElement("input");
	el.setAttribute("type", "text");
	el.setAttribute("name", "dst_addr" + iteration);
	el.setAttribute("size", "16");
	el.setAttribute("value", "0.0.0.0");
	cell.appendChild(el);
	// Insert input field for netmask
	cell = row.insertCell(2);
	el = document.createElement("input");
	el.setAttribute("type", "text");
	el.setAttribute("name", "dst_mask" + iteration);
	el.setAttribute("size", "16");
	el.setAttribute("value", "0.0.0.0");
	cell.appendChild(el);
	// end of ip address
		
	// Add port table
	// first create TABLE
	cell = fdefrow.insertCell(4);
	el = document.createElement("table");
	el.setAttribute("id", "port" + iteration);
	cell.appendChild(el);
	// Insert row
	tbl = document.getElementById("port" + iteration);
	lastRow = tbl.rows.length;
	row = tbl.insertRow(lastRow);
	// Insert cell "Джерело"
	cell = row.insertCell(0);
	textNode = document.createTextNode("Джерело");
	cell.appendChild(textNode);
	// Insert input field	
	cell = row.insertCell(1);
	el = document.createElement("input");
	el.setAttribute("type", "text");
	el.setAttribute("name", "src_port" + iteration);
	el.setAttribute("size", "8");
	el.setAttribute("value", "0");
	cell.appendChild(el);
	// Insert second row
	row = tbl.insertRow(lastRow + 1);
	// Insert cell "Отримувач"
	cell = row.insertCell(0);
	textNode = document.createTextNode("Отримувач");
	cell.appendChild(textNode);
	// Insert input field	
	cell = row.insertCell(1);
	el = document.createElement("input");
	el.setAttribute("type", "text");
	el.setAttribute("name", "dst_port" + iteration);
	el.setAttribute("size", "8");
	el.setAttribute("value", "0");
	cell.appendChild(el);
	// end of port table
		
	// Add AS table
	// first create TABLE
	cell = fdefrow.insertCell(5);
	el = document.createElement("table");
	el.setAttribute("id", "as" + iteration);
	cell.appendChild(el);
	// Insert row
	tbl = document.getElementById("as" + iteration);
	lastRow = tbl.rows.length;
	row = tbl.insertRow(lastRow);
	// Insert cell "Джерело"
	cell = row.insertCell(0);
	textNode = document.createTextNode("Джерело");
	cell.appendChild(textNode);
	// Insert input field	
	cell = row.insertCell(1);
	el = document.createElement("input");
	el.setAttribute("type", "text");
	el.setAttribute("name", "src_as" + iteration);
	el.setAttribute("size", "8");
	el.setAttribute("value", "0");
	cell.appendChild(el);
	// Insert second row
	row = tbl.insertRow(lastRow + 1);
	// Insert cell "Отримувач"
	cell = row.insertCell(0);
	textNode = document.createTextNode("Отримувач");
	cell.appendChild(textNode);
	// Insert input field	
	cell = row.insertCell(1);
	el = document.createElement("input");
	el.setAttribute("type", "text");
	el.setAttribute("name", "dst_as" + iteration);
	el.setAttribute("size", "8");
	el.setAttribute("value", "0");
	cell.appendChild(el);
	// end of port table

	// ADD remove button
	cell = fdefrow.insertCell(6);
	el = document.createElement("input");
	el.setAttribute("type", "button");
	el.setAttribute("value", "X");
	el.setAttribute("id", iteration);
	el.onclick = delrow;
	cell.appendChild(el);
	
		
}
							
function delrow()
{
	tbl = document.getElementById("fdef");
	id = window.event.srcElement.id;
	row_id = document.getElementById('row' + id).rowIndex;
	tbl.deleteRow(row_id);
}
							</script>
			 				<table align="center" cellspacing="1" cellpadding="2" border="0" bgcolor="silver" width="100%" id="fdef">
								<tr bgcolor="lightgreen">
									<td>Протокол</td>
									<td>Часовий ╕нтервал</td>
									<td>╤нтерфейс</td>
									<td>Мережа</td>
									<td>Порт</td>
									<td>AS</td>
									<td><input type="button" name="addrow" value="+" onclick="addRowToTable();"></td>
								</tr>
<?
	$query = "SELECT
			id,
			proto, 
			timerange, 
			in_if, 
			out_if,
			INET_NTOA(src_addr) as src_addr,
			INET_NTOA(src_mask) as src_mask,
			INET_NTOA(dst_addr) as dst_addr,
			INET_NTOA(dst_mask) as dst_mask,
			src_port,
			dst_port,
			src_as,
			dst_as 
			FROM filter_definition 
			WHERE filter_id = '$filter_id' 
			ORDER BY id";
	$res = mysql_query($query);
	if(FALSE == $res){
		$msg = "Error: ".mysql_error()." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		mysql_close($mysql);
		exit(1);
		}
	$num = mysql_num_rows($res);
	for($i = 0; $i < $num; $i++){
		$def_id = mysql_result($res, $i, "id");
		$proto = mysql_result($res, $i, "proto");
		$timerange = mysql_result($res, $i, "timerange");
		$in_if = mysql_result($res, $i, "in_if");
		$out_if = mysql_result($res, $i, "out_if");
		$src_addr = mysql_result($res, $i, "src_addr");
		$src_mask = mysql_result($res, $i, "src_mask");
		$dst_addr = mysql_result($res, $i, "dst_addr");
		$dst_mask = mysql_result($res, $i, "dst_mask");
		$src_port = mysql_result($res, $i, "src_port");
		$dst_port = mysql_result($res, $i, "dst_port");
		$src_as = mysql_result($res, $i, "src_as");
		$dst_as = mysql_result($res, $i, "dst_as");

?>
								<tr bgcolor="white" id="row<? echo $i + 1; ?>">
									<td><input type="text" name="proto<? echo $i?>" size="8" value="<?echo $proto?>"></td>
									<td><input type="text" name="timerange<? echo $i?>" size="16" value="<?echo $timerange?>"></td>
									<td>
										<table>
											<tr>
												<td>Вх╕дний</td>
												<td><input type="text" name="in_if<? echo $i?>" size="8" value="<?echo $in_if?>"></td>
											</tr>
											<tr>
												<td>Вих╕дний</td>
												<td><input type="text" name="out_if<? echo $i?>" size="8" value="<?echo $out_if?>"></td>
											</tr>
										</table>
									</td>
									<td>
										<table>
											<tr>
												<td>Джерело</td>
												<td><input type="text" name="src_addr<? echo $i?>" size="16" value="<?echo $src_addr?>"></td>
												<td><input type="text" name="src_mask<? echo $i?>" size="16" value="<?echo $src_mask?>"></td>
											</tr>
											<tr>
												<td>Отримувач</td>
												<td><input type="text" name="dst_addr<? echo $i?>" size="16" value="<?echo $dst_addr?>"></td>
												<td><input type="text" name="dst_mask<? echo $i?>" size="16" value="<?echo $dst_mask?>"></td>
											</tr>
										</table>
									</td>
									<td>
										<table>
											<tr>
												<td>Джерело</td>
												<td><input type="text" name="src_port<? echo $i?>" size="8" value="<?echo $src_port?>"></td>
											</tr>
											<tr>
												<td>Отримувач</td>
												<td><input type="text" name="dst_port<? echo $i?>" size="8" value="<?echo $dst_port?>"></td>
											</tr>
										</table>
									</td>
									<td>
										<table>
											<tr>
												<td>Джерело</td>
												<td><input type="text" name="src_as<? echo $i?>" size="8" value="<?echo $src_as?>"></td>
											</tr>
											<tr>
												<td>Отримувач</td>
												<td><input type="text" name="dst_as<? echo $i?>" size="8" value="<?echo $dst_as?>"></td>
											</tr>
										</table>
									</td>
									<td>
										<input type="button" value="X" id=<? echo $i + 1; ?> onclick="delrow()">
									</td>
									<input type="hidden" name="def_id<?echo $i?>" value="<?echo $def_id?>">
								</tr>
<?
		}
?>
								<input type="hidden" name="num" value="<?echo $num?>">
								<input type="hidden" name="filter_id" value="<?echo $filter_id;?>">
								<input type="hidden" name="client_id" value="<?echo $client_id;?>">
				</table>
	     </td>
	 		</tr>
	 		<tr>
	 			<td colspan="2">
					<table>
						<tr>
	 						<td><input type="submit" name="add_row" value="Додати умову"></td>
	 						<td><input type="submit" name="del_row" value="Видалити умову"></td>
							<td><input type="submit" name="save" value="Зберегти ф╕льтр"></td>
	 					</tr>
					</table>
				</td>
		</tr>
		<tr>
			<td colspan="2"><a href="<?echo "show_client.php?client_id=".$client_id ?>">Повернутися</a></td>
		</tr>
	</table>
 </form>
 </tr>
<?


	mysql_close($mysql);
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
