<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  readfile("$DOCUMENT_ROOT/head.html");
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
  $client_id = mysql_escape_string($_GET["client_id"]);
	$type = mysql_escape_string($_GET["type"]);
  $query = "SELECT
		DATE_FORMAT(charge.timestamp, '%Y-%b') AS datestamp,
		DATE_FORMAT(charge.timestamp, '%Y') AS year,
		DATE_FORMAT(charge.timestamp, '%m') AS month,
    SUM(charge.value_without_vat) AS value,
    service.description
    FROM service
    LEFT JOIN contract ON contract.id = service.contract_id
    LEFT JOIN charge ON service.id = charge.service_id
    WHERE contract.client_id = '$client_id'";
  if($type == "other"){
		if(FALSE == check_superpasswd($mysql, $login)){
			$msg = "Нев╕рно введено пароль";
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_close($mysql);
			exit(1);
			}
		}
	if($type == "other"){
		$query .= " AND service.cash = 'yes'";
		}
	else{
		$query .= " AND service.cash = 'no'";
		}
	$query .= " GROUP BY datestamp, service.description";
	$query .= " ORDER BY charge.timestamp DESC";
	$result = mysql_query($query);
	if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($result);
?>
      <tr>
				<td width="20%" valign="top">
<? include("left_cl.php");?>
				</td>
        <td valign="top">
          <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
						<tr bgcolor="white">
							<td colspan="3" align="right">
								<a href="<? echo $_SERVER["PHP_SELF"]."?type=other&amp;client_id=".$client_id; ?>">╤нш╕ нарахування</a>
							</td>
						</tr>
            <tr bgcolor="lightgrey">
              <th>Послуга</th>
              <th>Пер╕од</th>
              <th>Нараховано без ПДВ</th>
            </tr>
<?
  $color1 = "#E4E4E4";
  $color2 = "white";
	for($i = 0; $i < $n; $i++){
	$color = $color == $color1 ? $color2 : $color1;
	$year = mysql_result($result, $i, "year");
	$month = mysql_result($result, $i, "month");
	$month = strftime("%B", mktime(0, 0, 0, $month, 1, $year));
?>
						<tr bgcolor="<? echo $color;?>" onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='<? echo $color; ?>'">
              <td><? echo mysql_result($result, $i, "service.description");?></td>
              <td><? echo $year." ".$month;?></td>
              <td align="right"><? echo number_format(mysql_result($result, $i, "value"), 2, ".", "");?></td>
            </tr>
<?
    }
?>
          </table>
        </td>
      </tr>
<?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
