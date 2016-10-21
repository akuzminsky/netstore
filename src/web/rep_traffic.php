<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  include("$DOCUMENT_ROOT/netstorecore.php");
  session_start();
  if(!@session_is_registered('authdata')){
    header($start_url);
    }
  if($_SESSION['authdata']['permlevel'] != 'admin' 
      && $_SESSION['authdata']['permlevel'] != 'topmanager'){
    header($start_url);
    }
 
  $authdata = $_SESSION['authdata'];
  $login = $authdata['login'];
  $passwd = $authdata['passwd'];
  $permlevel = $authdata['permlevel'];
  $mysql = @mysql_connect($host, $login, $passwd); mysql_set_charset("koi8u");
  if($mysql == FALSE){
    session_timeout();
		}
  if(FALSE == mysql_select_db($db)){
    $msg = "Error: ".mysql_error()." while connecting to database ".$db;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  $REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
  if($REQUEST_METHOD == "GET"){
  	readfile("$DOCUMENT_ROOT/head.html");
  	include("top.php");
?>
  <tr>
    <td valign="top" width="20%"><? include("left_rp.php");?></td>
    <td width="80%" valign="top">
			<form name="register_by_period" method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
			<table border="0">
			<caption>Траф╕к кл╕╓нт╕в</caption>
				<tr>
					<td>
						<table>
							<tr>
								<th>Пер╕од</th>
								<td>
									<select name="m">
<?
	$m = date("m");
	$m = ($m == 1) ? 12 : $m - 1;
  for($j = 1; $j <= 12; $j++){
?>
										<option <? if($j == $m){ echo "selected"; }?> value="<? echo $j;?>"><? echo strftime("%OB", mktime(0, 0, 0, $j, 1, 2000));?></option>
<?
		}
?>
									</select>
								</td>
								<td>
									<select name="y">
<?
	$y = date("Y");
	$y = ($m == 12) ? $y -1 : $y;
	for($j = $y - 3; $j <= $y + 5; $j++){
?>
										<option <? if($j == $y){ echo "selected"; }?>><? echo $j;?></option>
<?
		}
?>
									</select>																	
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table>
							<tr>
								<th>Формат</th>
								<td nowrap>
									<input type="radio" name="format" value="html" checked id="format_html"><label for="format_html">HTML</label>
									<input type="radio" name="format" value="csv" id="format_csv"><label for="format_csv">CSV</label>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table>
							<tr>
								<th>Кодування</th>
								<td nowrap>
									<input type="radio" name="encoding" value="cp1251" checked id="encoding_cp1251"><label for="encoding_cp1251">cp1251</label>
									<input type="radio" name="encoding" value="koi8-u" id="encoding_koi8-u"><label for="encoding_koi8-u">koi8-u</label>
									<input type="radio" name="encoding" value="x-cp866" id="encoding_x-cp866"><label for="encoding_x-cp866">cp866</label>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<th><input type="submit" name="generate" value="Сформувати"></th>
				</tr>
			</table>
			</form>
		</td>
	</tr>

<?
  	readfile("$DOCUMENT_ROOT/bottom.html");
		exit(0);
		}
	if($REQUEST_METHOD == "POST"){
		$format = mysql_escape_string($_POST["format"]);
		$year = mysql_escape_string($_POST["y"]);
		$month = mysql_escape_string($_POST["m"]);
		$encoding = mysql_escape_string($_POST["encoding"]);
		// Starting transaction
    if(mysql_query("BEGIN") == FALSE){
			$msg = "Error: ".mysql_error()." while starting transaction and executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
      mysql_close($mysql);
      exit(1);
      }
		// Получаем трафик клиентов
		$query = "SELECT
			client.id,
			cluster.id,
			client.description,
			client.full_name,
			cluster.description,
			SUM(traffic_snapshot.incoming + traffic_snapshot.outcoming)/1048576 AS traffic
			FROM client
			LEFT JOIN client_cluster ON client_cluster.client_id = client.id
			LEFT JOIN cluster ON client_cluster.cluster_id = cluster.id
			LEFT JOIN traffic_snapshot ON traffic_snapshot.client_id = client.id
			WHERE
			traffic_snapshot.timestamp >= '$year-$month-01'
			AND traffic_snapshot.timestamp < DATE_ADD('$year-$month-01', INTERVAL 1 MONTH)
			AND client.activation_time <= DATE_ADD('$year-$month-01', INTERVAL 1 MONTH)
			AND ( client.inactivation_time >= '$year-$month-01' 
				OR client.inactivation_time IS NULL
				OR client.inactivation_time = '000-00-00')
			GROUP BY client.id
			HAVING cluster.description <> 'Службовий' OR cluster.description IS NULL
			ORDER BY cluster.description, client.description";
		$result = mysql_query($query);
    if($result == FALSE){
			$msg = "Error: ".mysql_error()." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
      mysql_close($mysql);
      exit(1);
      }
    $n = mysql_num_rows($result);
		switch($format){
			case "html":
  			readfile("$DOCUMENT_ROOT/head.html");
  			include("top.php");
				header("Content-Type: text/html");
?>
  <tr>
    <td valign="top" width="20%"><? include("left_rp.php");?></td>
    <td width="80%">
			<table width="100%" cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
			<caption>Траф╕к кл╕╓нт╕в за <b><? echo strftime("%OB", mktime(0, 0, 0, $month, 1, $year))." ".$year; ?></b> року</caption>
				<tr bgcolor="lightgrey">
					<th># п/п</th>
					<th>Б╕знес-центр</th>
					<th>Скорочена назва</th>
					<th>Абонент</th>
					<th>Траф╕к,(МБ)</th>
				</tr>
				
<?
	$color1 ="#FFF4AB";
	$color2 = "white";
	$color = $color1;
	$sum = 0;
	for($i = 0; $i < $n; $i++){
		$description = mysql_result($result, $i, "client.description");
		$client_id = mysql_result($result, $i, "client.id");
		$cluster_id = mysql_result($result, $i, "cluster.id");
		$cl_description = mysql_result($result, $i, "cluster.description");
		$cl_description = ($cl_description == "") ? "Вид╕лена л╕н╕я": $cl_description;
		$full_name = mysql_result($result, $i, "client.full_name");
		if($full_name == ""){
			$full_name = $description;
			}
		$traffic = mysql_result($result, $i, "traffic");
		$sum += $traffic;
		$color = ($color == $color1) ? $color2: $color1;
?>
				<tr bgcolor="<? echo $color; ?>">
					<td><? echo $i + 1; ?></td>
					<td align="left"><a href="show_ll.php?cluster_id=<? echo $cluster_id; ?>"><? echo $cl_description; ?></a></td>
					<th align="left"><a href="show_client.php?client_id=<? echo $client_id; ?>"><? echo $description; ?></a></th>
					<th align="left"><a href="show_client.php?client_id=<? echo $client_id; ?>"><? echo $full_name; ?></a></th>
					<td align="right" nowrap><? echo number_format($traffic, 2, ".", " ");?></td>
				</tr>
<?
  	}
?>	
				<tr bgcolor="<? echo $color; ?>">
					<td align="right" colspan="3">Сума</td>
					<td align="right" nowrap><b><? echo number_format($sum, 2, ".", " ");?></b></td>
				</tr>
			</table>
		</td>
	</tr>
<?
  			readfile("$DOCUMENT_ROOT/bottom.html");
				break;
			case "csv":
				$fcsv_path = tempnam($TMPDIR, "traffic_csv.".session_id());
				$fcsv = fopen($fcsv_path, "w+");
				if($fcsv == FALSE){
					$msg = "Не вдалося в╕дкрити файл '$fcsv_path' для запису";
					$msg = str_replace("\n", "<br>", $msg);
					$msg = urlencode($msg);
					header ("Location: show_error.php?error=".$msg);
					mysql_query("ROLLBACK");
			    mysql_close($mysql);
			    exit(1);
					}
				$header = "\"# п/п\"" . ",";
				$header .= "\"Б╕знес-центр\"" . ",";
				$header .= "\"Скорочена назва\"" . ",";
				$header .= "\"Абонент\"" . ",";
				$header .= "\"Траф╕к(МБ)\"" . "\n";
				switch($encoding) {
					case "cp1251" : $enc_code = "w"; break;
					case "koi8-u" : $enc_code = "k"; break;
					case "x-cp866" : $enc_code = "d"; break;
					default : $enc_code = "k";
					}
				$header =	convert_cyr_string($header, "k", $enc_code) ;
				fwrite($fcsv, $header);
				for($i = 0; $i < $n; $i++){
					$description = mysql_result($result, $i, "client.description");
					$cl_description = mysql_result($result, $i, "cluster.description");
					$cl_description = ($cl_description == "") ? "Вид╕лена л╕н╕я": $cl_description;
					$full_name = mysql_result($result, $i, "client.full_name");
					if($full_name == ""){
						$full_name = $description;
						}
					$traffic = mysql_result($result, $i, "traffic");
					
					$buf = $i + 1 . ",";
					$buf .= "\"" . str_replace("\"", "'", $cl_description) . "\"" . ",";
					$buf .= "\"". str_replace("\"", "'", $description) . "\"" . ",";
					$buf .= "\"". str_replace("\"", "'", $full_name) . "\"" . ",";
					$buf .= $traffic . "\n";
					$buf =	convert_cyr_string($buf, "k", $enc_code);
					fwrite($fcsv, $buf);
        	}
				fclose($fcsv);
				chmod($fcsv_path, 0644);
				header("Content-Type: text/csv");
				header("Content-disposition: inline; filename=register-$year-$month-$encoding.csv");
				header("Content-Length: ".filesize($fcsv_path));
				readfile($fcsv_path);
				unlink($fcsv_path);
				break;
			case "dbf":
// DBF format
//
//struct DB_HEADER {
//  unsigned char version;      /* Byte: 0; dBase version */
//  unsigned char last_update[3];   /* Byte: 1-3; date of last update */
//    //unsigned long records;    /* Byte: 4-7; number of records in table */
//    unsigned int records;     /* Byte: 4-7; number of records in table */
//  u_int16_t header_length;    /* Byte: 8-9; number of bytes in the header */
//  u_int16_t record_length;    /* Byte: 10-11; number of bytes in the record */
//  unsigned char reserved01[2];  /* Byte: 12-13; reserved, see specification of dBase databases */
//  unsigned char transaction;    /* Byte: 14; Flag indicating incomplete transaction */
//  unsigned char encryption;   /* Byte: 15; Encryption Flag */
//  unsigned char reserved02[12]; /* Byte: 16-27; reserved for dBASE in a multiuser environment*/
//  unsigned char mdx;        /* Byte: 28; Production MDX file flag */
//  unsigned char language;     /* Byte: 29; Language driver ID */
//  unsigned char reserved03[2];  /* Byte: 30-31; reserved, filled with zero */
//};
//struct DB_FIELD {
//  unsigned char field_name[11]; /* Byte: 0-10; fieldname in ASCII */
//  unsigned char field_type;   /* Byte: 11; field type in ASCII (C, D, L, M or N) */
//  //unsigned long field_adress;   /* Byte: 12-15; field data adress */
//  u_int32_t field_adress;   /* Byte: 12-15; field data adress */
//  unsigned char field_length;   /* Byte: 16; field length in binary */
//  unsigned char field_decimals; /* Byte: 17; field decimal count in binary */
//  unsigned char reserved[13];   /* Byte: 18-30; reserved */
//  unsigned char mdx;        /* Byte: 31; Production MDX field flag */
//};
				switch($encoding) {
					case "cp1251" : $enc_code = "w"; break;
					case "koi8-u" : $enc_code = "k"; break;
					case "x-cp866" : $enc_code = "d"; break;
					default : $enc_code = "k";
					}
				$f_size = 200;
				$DB_HEADER = 32; // Length of DBF header
				$DB_FIELD = 32;  // Length of DBF field
				// version
				$dbf_register = chr(0x03);
				// last_update
				$dbf_register .= chr(date("y") + 100);
				$dbf_register .= chr(date("n"));
				$dbf_register .= chr(date("j"));
				// records
				$records = mysql_num_rows($result);
				$dbf_register .= chr(($records >>  0) & 0x000000FF);
				$dbf_register .= chr(($records >>  8) & 0x000000FF);
				$dbf_register .= chr(($records >> 16) & 0x000000FF);
				$dbf_register .= chr(($records >> 24) & 0x000000FF);
				// header_length
				$header_length = $DB_HEADER + (mysql_num_fields($result) + 6 - 9) * $DB_FIELD + 1;
				$dbf_register .= chr(($header_length >> 0) & 0x000000FF);
				$dbf_register .= chr(($header_length >> 8) & 0x000000FF);
				// record_length
				$record_length = $f_size * (mysql_num_fields($result) + 6 - 9) + 1;
				$dbf_register .= chr(($record_length >> 0) & 0x000000FF);
				$dbf_register .= chr(($record_length >> 8) & 0x000000FF);
				//reserved01[2]
				$dbf_register .= chr(0).chr(0);
				// transaction
				$dbf_register .= chr(0);
				// encryption
				$dbf_register .= chr(0);
				// reserved02[12]
				$dbf_register .= chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0);
				// mdx
				$dbf_register .= chr(0);
				// language
				$dbf_register .= chr(0);
				// reserved03[2]
				$dbf_register .= chr(0).chr(0);
				
				$fields = array();
				$fields[] = "NUMREC";
				$fields[] = "CLIENT_ID";
				$fields[] = "NAME";
				$fields[] = "CONTRACT";
				for($i = 0; $i < mysql_num_rows($list_services); $i++){
					$fields[] = "F".$i;
					}
				$fields[] = "WITHOUTTAC";
				$fields[] = "WITHTAC";
				// Now make a DB_FIELD
				foreach($fields as $field_name){
					// field_name[11]
					$dbf_register .= substr(sprintf("%'".chr(0)."-11s", $field_name), 0, 11);
					// field_type
					$dbf_register .= "C";
					// field_adress
					$dbf_register .= chr(0).chr(0).chr(0).chr(0);
					// field_length
					$dbf_register .= chr($f_size);
					// field_decimals
					$dbf_register .= chr(0);
					// reserved[13]
					$dbf_register .= chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0);
					// mdx
					$dbf_register .= chr(0);
					}
				//tail = 0x0D;
				$dbf_register .= chr(0x0D);
				// Now make records
				$npp = 1;
    		while($vals = mysql_fetch_row($result)){
					$description = $vals[0];
					$contract_id = $vals[1];
					$client_id = $vals[2];
					$c_type = $vals[3];
					$c_number = $vals[4];
					$tac_status = $vals[5];
					$cluster_id = $vals[6];
					$cl_description = $vals[7];
					$full_name = $vals[8];
					if($full_name == ""){
						$full_name = $description;
						}
					$dbf_register .= chr(0x20);
					$dbf_register .= substr(sprintf("%-".$f_size."u", $npp), 0, $f_size);
					$dbf_register .= substr(sprintf("%-".$f_size."u", $client_id), 0, $f_size);
					$dbf_register .= convert_cyr_string(substr(sprintf("%-".$f_size."s", $full_name), 0, $f_size), "k", $enc_code);
					$dbf_register .= convert_cyr_string(substr(sprintf("%-".$f_size."s", $c_type."-".$c_number), 0, $f_size), "k", $enc_code);
					$service_offset = 9;
					$sum_without_vat = 0;
					for($i = $service_offset; $i < count($vals); $i++){
						$value = $vals[$i];
						$sum_without_vat = round2($sum_without_vat + $value);
						$dbf_register .= substr(sprintf("%-".$f_size."s", number_format($value, 2, ".", "")), 0, $f_size);
						}
					$sum_with_vat = round2($sum_without_vat * (1 + $vat));
					$dbf_register .= substr(sprintf("%-".$f_size."s", number_format($sum_without_vat, 2, ".", "")), 0, $f_size);
					$dbf_register .= substr(sprintf("%-".$f_size."s", number_format($sum_with_vat, 2, ".", "")), 0, $f_size);
					$npp++;
        	}
				$dbf_register .= chr(0x1A);	

				header("Content-Type: application/octet-stream");
				header("Content-disposition: inline; filename=register-$year-$month-$encoding.dbf");
				header("Content-Length: ".strlen($dbf_register));
				echo $dbf_register;
				break;
			default:
				$msg = "Нев╕домий формат '$format'";
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".$msg);
				exit(1);
			}
		mysql_query("COMMIT");
		exit(0);
		}
?>
