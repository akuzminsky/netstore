<?
	$today = getdate();
	$lbk_year = $today["year"];
	$lbk_month = $today["mon"];
	if($type == "other"){
		if(FALSE == check_superpasswd($mysql, $login)){
			$msg = "��צ��� ������� ������";
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_close($mysql);
			exit(1);
			}
		}
	/*
	$color1 = "#16A9A0";
	$color2 = "#20F5E7";
	*/
	$color1 = "white";
	$color2 = "white";
?>
<table>
<?
	switch($permlevel){
		case "admin":
		case "accountoperator":
		case "manager":
		case "topmanager":
?>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_bc.php">������ ¦����-����Ҧ�</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_clients.php?cluster_id=0">������ �������� ��Ħ����� ̦Φ�</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_traffic.php">���Ʀ�</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="repallclientscur_with_prev.php">���Ʀ� �̦����� �� �������� �� �������Φ� ͦ��æ</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="repallbccur.php">���Ʀ� ¦����-����Ҧ� �� ��Ħ�����</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="filteroverquotedreport.php">������ �̦�����, �˦ ���������� ̦ͦ� ���Ʀ��</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_date_connection.php">������ �̦��Ԧ�, Ц��������� �� ��Ҧ��</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_cluster_activity.php">������ ¦����-����Ҧ�, ���������/צ��������� �� ��Ҧ��</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_payments.php?type=<? echo $type; ?>">�צ� �� �������</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_bill.php">��Ȧ� �����˦�</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_stwa.php">��Ȧ� ��Ԧ� ��������� ��¦�</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_notification.php">��Ȧ� ��צ�������</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_saldo.php?type=<? echo $type; ?>">������</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_minsaldo.php?type=<? echo $type; ?>">�������� � ͦΦ������� Ҧ���� ������</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_unbill.php?type=<? echo $type; ?>">�̦����, ���� �� ���������� �����˦�</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_unlimited.php">�̦���� � ����������� ���Ʀ���</a></td>
	</tr>
<?
	case "support":
?>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="client_by_ip.php">����� �̦���� �� ���� IP-�������</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="client_by_email.php">����� �̦���� �� ����������� �������</a></td>
	</tr>
<?
	case "juniorsupport":
?>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="locked_customers.php">������ ��������� �������� �̦��Ԧ�</a></td>
	</tr>
<?
		}
?>
</table>
