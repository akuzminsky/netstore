<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  $REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
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

	if(file_exists("/var/run/netflowstaff.lock")){
    $msg = "������� �� ������ ���� ���������Φ, ��˦���� ����� ��� ����������� ����������� ����Ʀ�, ��� ������������ ����� �������ϧ ����������";
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    exit(1);
		}
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
  if($REQUEST_METHOD == "POST"){
    $y = mysql_escape_string($_POST["y"]);
    $m = mysql_escape_string($_POST["m"]);
    $y_notify = mysql_escape_string($_POST["y_notify"]);
    $m_notify = mysql_escape_string($_POST["m_notify"]);
    $d_notify = mysql_escape_string($_POST["d_notify"]);
    //echo "<pre>\n";
		//print_r($_POST);
		//print_r(array_keys($_POST["cl_"]));
     //echo "</pre>\n";
		$pbwidth = 300;
?>
	<tr>
		<td align="left" valign="top" width="20%"><?include("left_bk.php");?></td>
		<td width="80%">
			<table>
			<caption>����������� �������. ���� �����, ���������...</caption>
			<form name="pbform">
				<tr>
					<td width="<? echo $pbwidth; ?>"><img name="pb" src="img/gdot.png" height="20" width="<? echo $pbwidth; ?>"></td>
					<td><input type="text" name="pbtext" value="0" border="0" readonly></td>
				</tr>
				<tr>
<?
	$msg = "�� �Ӧ ������� ���� �����������.\n�����������, ���� �����, ���� �������� ��������� �����˦� ��˦����� ���� ������";
	$msg = str_replace("\n", "<br>", $msg);
	$msg = urlencode($msg);
	$loc = "show_error.php?error=".$msg;
?>
					<td colspan="2"><a href="<? echo $loc; ?>" name="pblink" target="_new">����������� ���������Φ �������</a></td>
				</tr>
				<tr>
					<td colspan="2"><a href="<? echo $loc; ?>" name="pblink_notification" target="_new">����������� ��צ��������</a></td>
				</tr>
			</form>
			</table>
		</td>
	</tr>
						
<?
		readfile("$DOCUMENT_ROOT/bottom.html");
		$bills = array();
		$notifications = array();
		ob_end_flush();
		$num_bills = 0;
		foreach(array_keys($_POST["cl_"]) as $cluster_id){
     	foreach(array_values($_POST["cl_"]["$cluster_id"]) as $contract_id){
				$num_bills ++;
				}
			}
		$step = $pbwidth / $num_bills;
		$width = 0;
		$query = "SELECT 
		    contract.id,
		    client.email
		    FROM contract
		    LEFT JOIN client ON client.id = contract.client_id";
		$result = mysql_query($query);
		if($result == FALSE){
		  $msg = "Error: ".mysql_error()." while executing:\n".$query;
		  $msg = str_replace("\n", "<br>", $msg);
		  $msg = urlencode($msg);
		  header ("Location: show_error.php?error=".$msg);
		  mysql_close($mysql);
		  exit(1);
		  }
		$n = mysql_num_rows($result);
		$emails = array();
		for($i = 0; $i < $n; $i++){
			$contract_id = mysql_result($result, $i, "contract.id");
			$emails[$contract_id] = mysql_result($result, $i, "client.email");
			}
		foreach(array_keys($_POST["cl_"]) as $cluster_id){
     	foreach(array_values($_POST["cl_"]["$cluster_id"]) as $contract_id){
				//echo "Contract#: ".$contract_id."; Period: $y-$m<br>";
				$bill_id = add_bill($mysql, $contract_id, $y, $m);
				$bills[] = $bill_id;
				$notifications[] = add_notification($mysql, $bill_id, $y_notify, $m_notify, $d_notify);
				// Send invoice by e-mail
				$to_email = $emails[$contract_id];
				$f_path = print_bill($mysql, $bill_id, 1, "Invoice_".$bill_id.".pdf", "inline", false, true);
				mail_attach("Accounting Department <account@nbi.ua>", $to_email, "Invoice", 
"
�������� ��������.

���������� ��� �������-�������  �� ������ ������ �������� צ� ". strftime("%d %B %Y�.")."
������� �Ħ������ ������ �������� ����� ���˦������ �Φ�.

� �������,

--
����������� צ�Ħ� ��� \"��æ������� ���� �������æ�\"

P.S.
    ���� � �������� ������������ ��� � �����Ԧ Adobe Portable Document Format (PDF).
    ��� ����������� �������, ��� ����Ȧ��� ���� ������������ �������� \"Adobe Reader\",
    ��� �� ������ ����������� � ������ FTP ������� �� ����� �������
    ftp://ftp.nbi.com.ua/pub/SoftWare/Windows/Office/AcroReader51_ENU.exe
", $f_path, "Invoice_$bill_id.pdf");
				$width += $step;
				echo "<script language=\"javascript\">\n";
				echo "window.document.images('pb').width = ". intval($width) . ";\n";
				echo "window.document.forms['pbform'].elements['pbtext'].value = '". intval($width * 100 / $pbwidth) . " %';\n";
				echo "</script>\n";
				flush();
				}
			}
		$fbill = print_bill($mysql, $bills, 2, "NetStoreBills_".sprintf("%04u", $y)."-".sprintf("%02u", $m).".pdf", "notinline", false, false);
		$fnote = print_notification($mysql, $notifications, 2, "Notifications_".sprintf("%04u", $y)."-".sprintf("%02u", $m).".pdf", "notinline", false);
		echo "<script language=\"javascript\">document.anchors('pblink').href='send_pdf.php?file=". basename($fbill) ."';</script>\n";
		echo "<script language=\"javascript\">document.anchors('pblink_notification').href='send_pdf.php?file=". basename($fnote) ."';</script>\n";
		echo "<script language=\"javascript\">\n";
		echo "window.document.images('pb').width = ". intval($pbwidth) . ";\n";
		echo "window.document.forms['pbform'].elements['pbtext'].value = '���������';\n";
		echo "</script>\n";
    //echo "<pre>";
		//print_r($bills);
    //echo "</pre>";
		exit(0);
    }
  if($REQUEST_METHOD == "GET"){
    $year = mysql_escape_string($_GET["year"]);
    $month = mysql_escape_string($_GET["month"]);
    }
?>
  <tr>
    <td valign="top" width="20%"><? include("left_bk.php");?></td>
    <td width="80%">
      <form name="clients_form" method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
      <table cellspacing="1" cellpadding="2" bgcolor="silver">
				<tr bgcolor="white">
					<th colspan="3" align="right">���֦�� ��Ҧ��</th>
					<td>
						<select name="m">
<?
	for($j = 1; $j <= 12; $j++){
?>
							<option <? if($j == $month){ echo "selected"; }?> value="<? echo $j;?>"><? echo strftime("%OB", mktime(0, 0, 0, $j, 1, 2000));?></option>
<?
		}
?>
						</select>
						<select name="y">
<?
	$y = date("Y");
	for($j = $y - 3; $j <= $y + 5; $j++){
?>
							<option <? if($j == $year){ echo "selected"; }?>><? echo $j;?></option>
<?
		}
?>
												
						</select>
					</td>
				</tr>
				<tr bgcolor="white">
					<th colspan="3" align="right">���֦�� ���� צ���������� ��צ��������</th>
					<td>
						<select name="d_notify">
<?
	$day_notify = date("j");
	for($j = 1; $j <= 31; $j++){
?>
							<option <? if($j == $day_notify){ echo "selected"; }?> value="<? echo $j;?>"><? echo $j;?></option>
<?
		}
?>
						</select>
						<select name="m_notify">
<?
	$month_notify = date("n");
	for($j = 1; $j <= 12; $j++){
?>
							<option <? if($j == $month_notify){ echo "selected"; }?> value="<? echo $j;?>"><? echo strftime("%B", mktime(0, 0, 0, $j, 1, 2000));?></option>
<?
		}
?>
						</select>
						<select name="y_notify">
<?
	$year_notify = date("Y");
	for($j = $y - 3; $j <= $y + 5; $j++){
?>
							<option <? if($j == $year_notify){ echo "selected"; }?>><? echo $j;?></option>
<?
		}
?>
												
						</select>
					</td>
				</tr>
<?
  $query = "SELECT 
      contract.id,
      contract.c_type,
      contract.c_number,
      client.id,
      client.description,
      IFNULL(cluster.id, 0) AS cluster_id,
      IF(cluster.description IS NULL, '��Ħ���� ̦Φ�', cluster.description) AS cluster_description,
      SUM(IF(service.cash = 'no', 1, 0)) AS n_cash
      FROM contract
      LEFT JOIN service ON service.contract_id = contract.id
      LEFT JOIN client ON client.id = contract.client_id
      LEFT JOIN client_cluster ON client_cluster.client_id = client.id
      LEFT JOIN cluster ON cluster.id = client_cluster.cluster_id
			WHERE 
				(client.inactivation_time IS NULL
				OR 
				client.inactivation_time > NOW())
				AND (contract.expire_time = 0 
					OR contract.expire_time > '$year-$month-01'
					OR (YEAR(contract.expire_time) = YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH))
						AND 
						MONTH(contract.expire_time) = MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH)))
						)
      GROUP BY contract.id
      HAVING n_cash > 0
      ORDER BY cluster_description, client.description";
  $result = mysql_query($query);
  if($result == FALSE){
    $msg = "Error: ".mysql_error()." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($result);
  $cluster_description_prev = "";
  for($i = 0; $i < $n; $i++){
    $contract_id = mysql_result($result, $i, "contract.id");
    $c_type = mysql_result($result, $i, "contract.c_type");
    $c_number = mysql_result($result, $i, "contract.c_number");
    $client_id = mysql_result($result, $i, "client.id");
    $client_description = mysql_result($result, $i, "client.description");
    $cluster_id = mysql_result($result, $i, "cluster_id");
    $cluster_description = mysql_result($result, $i, "cluster_description");
    if($cluster_description != $cluster_description_prev){
      $cluster_description_prev = $cluster_description;
?>
        <tr bgcolor="white">
          <td><input type="checkbox" name="bc_<? echo $cluster_id?>" onClick=" return setCheckboxes('clients_form', <? echo $cluster_id; ?>, <? echo $contract_id; ?>);" checked></td>
          <th colspan="3" align="left"><? echo $cluster_description; ?></th>
        </tr>
<?
      }
?>
        <tr bgcolor="white">
          <td>&nbsp;</td>
          <td>
            <input type="checkbox" name="cl_[<? echo $cluster_id;?>][]" value="<? echo $contract_id; ?>" checked>
            <input type="hidden" name="contract_[<? echo $cluster_id;?>][]" value="<? echo $contract_id; ?>">
          </td>
          <td><a href="show_contract.php?client_id=<? echo $client_id; ?>"><? echo $c_type."-".$c_number; ?></a></td>
          <td><a href="show_client.php?client_id=<? echo $client_id; ?>"><? echo $client_description; ?></a></td>
        </tr>
<?
    }
?>    
        <tr bgcolor="white">
          <td colspan="4"><input type="submit" name="save" value="���������� �������"></td>
        </tr>
      </table>
    </td>
  </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
