<?
 $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
 readfile("$DOCUMENT_ROOT/head.html");
 include("$DOCUMENT_ROOT/netstorecore.php");
 session_start();
 if(!@session_is_registered('authdata')){
 	header($start_url);
	}
 if($_SESSION['authdata']['permlevel'] != 'admin' && $_SESSION['authdata']['permlevel'] != 'manager' && $_SESSION['authdata']['permlevel'] != 'client' && $_SESSION['authdata']['permlevel'] != 'topmanager'){
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
 if(isset($_GET[client_id])){
 	$client_id = $_GET[client_id];
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
	$_GET[client_id] = $client_id;
	}
 // If user has "client" privileges, check his validity
 if($_SESSION['authdata']['permlevel'] == 'client'){
 	if(!check_client_validity($client_id, $mysql)){
		header($start_url);
		}
 	}

 if($_SESSION['authdata']['permlevel'] == 'client'){
 ?>
 <tr>
 	<td valign=top align=right><a href=logout.php>�����</a></td>
 </tr>
 <?
 	}
 ?>
 <tr>
 	<td>
 		<table border=0 width=100%>
			<tr>
				<td valign=top>
				<p><center>��������� ������������.</center>
				<p>�� ���� �������� �� ������ �������� ������������ ��������� ������� ������������� �������� ��������. 
				��� ������� ��� �������������� ������� ������������� �������, �������������� ��� ��������.
				� ������� ���� ������� ����� ����� ������� ������� �������������� ������� ����������, � ������ ������� 
				�������� ������� ���, ��������, �������� ����������� ����� ����� ����������� ����������� �������� �������.
				<p>� ���������, ��-�� �������� ������� ������ ��� ����������� ������� ���������� ����� ������ � on-line 
				������. ������ �� ��������, ��� �� ������ ����� �� ��������� ��������� ���������� �� �������������� 
				������. ��� ����� ���� ���������� ����� ���������� ��������� ������� �������� ������ � ������������
				��������� ������ ����������.
				<p>�� ���������� ��� ��������� ������� ������� � ������ ������� �����������.
					<li> ������ �� ����. ���� ���� ���������� ����� ������� �� ������ ����.
					<li> ������ �� �����. � ���� ������ ������������� ����� ��������� ����������. ����� �������
						����������� � ������� ����������. �.�. ����� �������� 24 ������ ��� ������� ��� �� ���������
						�������.
					<li> ������ �����. ������ � ���� ������ ����������� �� �������. ��� ����� ��������� ����������,
						������� ��������������� ����� ������� ��������. ����� ������ ������
						����� ���� ������, ��� �������� ��������������� �������. �������� ����� ����� ����� ��� 
						��������� ��������������� ��� ���������� �����. �������� ������������ �� �� ����������� 
						���������� ����� ����� - ������ � ��� ����� ����� ��������������. ����� ���� ��� ����� 
						����� �����, �� �������� ������ ������� �� ��������� ���� ����� ����������� �����. 
					<li> ���� ��� �� ���������� ���������������� ������, �� ��������� ���� ����������� 
						� ����������� �� �����������. ���� �� ��� ������������ � ����������� �� ������ 
						<a href="mailto: noc@nbi.com.ua">noc@nbi.com.ua</a>
						</li>
						<p>����� ���� ��� �� ��������� ������ �����, ����� ���������� � ������� � ������������� 
						����������� � ������� ������. ��� ����� ����� ���� ������ ��� �� ����� ����
						����������� �� ������� ����������. ���������� � ������� ������ ����� ���� ������� ���
						�� ����� ���� SMS-����������.
						<p>
				</td>
			</tr>
			<tr>
				<td align=center><form action="submit_detailed_report.php" method=post>
					<table border=0>
						<tr><th align=center colspan=2>�������� ��� ������ ��� ������
						<tr><td><input type=radio name="report_type" id="radio_daily" value="daily" checked>
							<td><label for="radio_daily">������ �� ����
						<tr><td><input type=radio name="report_type" id="radio_hourly" value="hourly">
							<td><label for="radio_hourly">������ �� �����
						<tr><td><input type=radio name="report_type" id="radio_flows" value="flows">
							<td><label for="radio_flows">������ �� �������
						<tr><td align=center colspan=2><input type=submit name=subreport value="�����&gt;&gt;">
					</table>
					<input type=hidden name=client_id value="<?echo $client_id?>">
					<input type=hidden name=cl_login value="<?echo $login?>">
					</form>
				</td>
			</tr>
		</table>
	</td>
 </tr>
 <?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
