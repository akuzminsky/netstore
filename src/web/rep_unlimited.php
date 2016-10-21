<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  readfile("$DOCUMENT_ROOT/head.html");
  include("$DOCUMENT_ROOT/netstorecore.php");
  session_start();
  if(!@session_is_registered('authdata')){
    header($start_url);
    }
  $authdata = $_SESSION['authdata'];
  $login = $authdata['login'];
  $passwd = $authdata['passwd'];
  $permlevel = $authdata['permlevel'];
  if($permlevel != "admin" 
      && $permlevel != "manager" 
      && $permlevel != "topmanager"){
    header($start_url);
    }
  include("top.php");
  $mysql = @mysql_connect($host, $login, $passwd); mysql_set_charset("koi8u");
  if($mysql == FALSE){
    session_timeout();
    }
  if(FALSE == mysql_select_db($db)){
    $msg = mysql_error();
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  $REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
	if($REQUEST_METHOD == "GET"){
    $y1 = mysql_escape_string($_GET["y1"]);
    $m1 = mysql_escape_string($_GET["m1"]);
    $d1 = mysql_escape_string($_GET["d1"]);
    
		$order = mysql_escape_string($_GET["order"]);
		$dir = mysql_escape_string($_GET["dir"]);
		}
	if($REQUEST_METHOD == "POST"){
    $y1 = mysql_escape_string($_POST["y1"]);
    $m1 = mysql_escape_string($_POST["m1"]);
    $d1 = mysql_escape_string($_POST["d1"]);
    
		$order = mysql_escape_string($_GET["order"]);
		$dir = mysql_escape_string($_GET["dir"]);
		}
  if($y1 == "") $y1 = date("Y");
  if($m1 == "") $m1 = date("m");
  if($d1 == "") $d1 = date("d");
	$dfrom = mktime(0, 0, 0, $m1, $d1, $y1);
		
	if($order == ""){
		$order = "client.description";
		}
	if($dir == ""){
		$dir = "ASC";
		}
	mysql_query("BEGIN");
	$query = "SELECT 
			client.id,
			client.description,
			client.connection_speed,
			SUM(traffic_snapshot.incoming + traffic_snapshot.outcoming) AS traffic
			FROM client
			LEFT JOIN traffic_snapshot ON client.id = traffic_snapshot.client_id
			WHERE 
			client.unlimited = 'yes'
			AND
			YEAR(traffic_snapshot.timestamp) = YEAR('$y1-$m1-$d1')
			AND
			MONTH(traffic_snapshot.timestamp) = MONTH('$y1-$m1-$d1')
			AND
			client.activation_time <= '$y1-$m1-$d1'
			AND (client.inactivation_time IS NULL 
					OR client.inactivation_time > '$y1-$m1-$d1')
			GROUP BY client.id";
	$query .= " ORDER BY ".$order." ".$dir;
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
	$dir = ($dir == "ASC") ? "DESC" : "ASC";
?>
   <tr>
     <td>
       <table>
         <tr>
<?
  $today = getdate();
  for($iyear = $today["year"] + 1; $iyear >= $today["year"] - 5; $iyear--){
    if($y1 == $iyear){
?>
                <td><b><? echo $iyear?></b></td>
<?
      }
    else{
?>
								<td><a href="<? echo $PHP_SELF; ?>?y1=<? echo $iyear; ?>&amp;m1=<? echo $m1; ?>"><? echo $iyear?></a></td>
<?
      }
    }
?>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td>
            <table cellspacing="1" cellpadding="2" bgcolor="silver">
<?
  $imonth = 1;
  for($i = 1; $i <= 3; $i++){
?>
              <tr bgcolor="white">
<?
    for($j = 1; $j <= 4; $j++){
      $ts = mktime(0, 0, 0, $imonth, 1, $y1);
      $month_name = strftime("%OB", $ts);
      if($imonth == $m1){
?>
                <td><b><? echo $month_name; ?></b></td>
<?
        }
      else{
?>
								<td><a href="<? echo $PHP_SELF; ?>?y1=<? echo $y1; ?>&amp;m1=<? echo $imonth; ?>"><? echo $month_name; ?></a></td>
<?
        }
      $imonth++;
      }
?>
              </tr>
<?
    }
          
?>
        </table>
      </td>
    </tr>
    <tr>
      <td>
        <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver" width="100%">
				<caption>Список кл╕╓нт╕в з необмеженим траф╕ком</caption>
        	<tr bgcolor="#EDEDED">
        	  <th># п/п</th>
        	  <th><a href="<? echo $_SERVER["PHP_SELF"]."?order=client.description&amp;dir=".urlencode($dir).$url_ts?>">Назва кл╕╓нта</a></th>
        	  <th>Траф╕к в поточному м╕сяц╕, MB</th>
        	  <th>Максимально можливий траф╕к, MB</th>
        	</tr>
<?
  $n = mysql_num_rows($result);
	$sum_traffic = 0;
	$sum_max_possible = 0;
	for($i = 0; $i < $n; $i++){
		$client_id = mysql_result($result, $i, "client.id");
		$description = mysql_result($result, $i, "client.description");
		$traffic = mysql_result($result, $i, "traffic");
		$connection_speed = mysql_result($result, $i, "client.connection_speed");
		$max_possible = $connection_speed * 30 * 24 * 3600 * 1024 / 8;
		$sum_traffic += $traffic;
		$sum_max_possible += $max_possible;
		$bgcolor = ($traffic < $max_possible) ? "white" : "#FF8C8C";
?>
					<tr bgcolor="<? echo $bgcolor; ?>">
            <td align="right"><? echo $i + 1;?></td>
						<td><a href="show_client.php?client_id=<? echo $client_id; ?>"><? echo $description;?></a></td>
            <td align="right" nowrap><b><? echo number_format($traffic / 1024 / 1024, 2, ".", " "); ?></b></td>
            <td align="right" nowrap><b><? echo number_format($max_possible / 1024 / 1024, 2, ".", " "); ?></b></td>
          </tr>
<?
		}
?>
					<tr bgcolor="white">
            <td align="right">&nbsp;</td>
						<th>Всього</th>
            <td align="right" nowrap><b><? echo number_format($sum_traffic / 1024 / 1024, 2, ".", " "); ?></b></td>
            <td align="right" nowrap><b><? echo number_format($sum_max_possible / 1024 / 1024, 2, ".", " "); ?></b></td>
          </tr>
        </table>
      </td>
    </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
