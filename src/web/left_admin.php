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
?>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="operators.php">Оператори</a></td>
	</tr>
	<tr bgcolor="<? echo $color2; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="drwebavdesk.php">DrWeb AV-Desk</a></td>
	</tr>
<?
		break;
	case "support":
	case "juniorsupport":
?>
	<tr bgcolor="<? echo $color1; ?>">
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="drwebavdesk.php">DrWeb AV-Desk</a></td>
	</tr>
<?
		break;
	default:
?>
        <tr bgcolor="<? echo $color2; ?>">
                <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'">&nbsp;</td>
        </tr>
<?

		}
?>
</table>
