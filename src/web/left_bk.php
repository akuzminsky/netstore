<?
	$today = getdate();
	$lbk_year = $today["year"];
	$lbk_month = $today["mon"];
	if($lbk_month == 1){
		$lbk_year = $lbk_year - 1;
		$lbk_month = 12;
		}
	else{
		$lbk_month = $lbk_month - 1;
		}
?>
<table>

<?
	switch($authdata[permlevel]){
		case "support":
?>
	<tr>
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="sendmail.php">В╕дправка пов╕домлення електронною поштою</a></td>
	</tr>
<?			
			break;
	
		default:	
?>
	<tr>
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_rate.php?year=<? echo $lbk_year; ?>&amp;month=<? echo $lbk_month; ?>">Курс валют</a></td>
	</tr>
	<tr>
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="register.php?year=<? echo $lbk_year; ?>&amp;month=<? echo $lbk_month; ?>">Ре╓стр</a></td>
	</tr>
	<tr>
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="gen_bill_series.php?year=<? echo $lbk_year; ?>&amp;month=<? echo $lbk_month; ?>">Виставлення рахунк╕в</a></td>
	</tr>
	<tr>
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="gen_stwa_series.php?year=<? echo $lbk_year; ?>&amp;month=<? echo $lbk_month; ?>">Генерування акт╕в виконаних роб╕т</a></td>
	</tr>
	<tr>
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="pimport.php">╤мпорт оплат</a></td>
	</tr>
	<tr>
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="sendmail.php">В╕дправка пов╕домлення електронною поштою</a></td>
	</tr>
	<tr>
		<td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="set_counters.php">Номери документ╕в</a></td>
	</tr>
<?
	}	
?>
</table>
