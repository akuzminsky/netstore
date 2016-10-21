<?
	$today = getdate();
	$lbk_year = $today["year"];
	$lbk_month = $today["mon"];
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
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_bc.php">Список б╕знес-центр╕в</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_clients.php?cluster_id=0">Список активних вид╕лених л╕н╕й</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_traffic.php">Траф╕к</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="repallclientscur_with_prev.php">Траф╕к кл╕╓нтам за поточний та попередн╕й м╕сяц╕</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="repallbccur.php">Траф╕к б╕знес-центр╕в та вид╕ленок</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="filteroverquotedreport.php">Список кл╕╓нтов, як╕ перевищили л╕м╕т траф╕ка</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_date_connection.php">Список кл╕╓нт╕в, п╕дключених за пер╕од</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_cluster_activity.php">Список б╕знес-центр╕в, створених/в╕дключених за пер╕од</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_payments.php?type=<? echo $type; ?>">Зв╕т по оплатам</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_bill.php">Арх╕в рахунк╕в</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_stwa.php">Арх╕в акт╕в виконаних роб╕т</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_notification.php">Арх╕в пов╕домлень</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_saldo.php?type=<? echo $type; ?>">Сальдо</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_minsaldo.php?type=<? echo $type; ?>">Договори с м╕н╕мальним р╕внем сальдо</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_unbill.php?type=<? echo $type; ?>">Кл╕╓нти, яким не виставлено рахунк╕в</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="rep_unlimited.php">Кл╕╓нти з необмеженим траф╕ком</a></td>
	</tr>
<?
	case "support":
?>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="client_by_ip.php">Пошук кл╕╓нта за його IP-адресою</a></td>
	</tr>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="client_by_email.php">Пошук кл╕╓нта за електронною адресою</a></td>
	</tr>
<?
	case "juniorsupport":
?>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="locked_customers.php">Список тимчасово закритих кл╕╓нт╕в</a></td>
	</tr>
<?
		}
?>
</table>
