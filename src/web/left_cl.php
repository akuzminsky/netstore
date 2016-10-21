<?
	$q = "SELECT description FROM client WHERE id = '$client_id'";
	$r = mysql_query($q);
	if($r == FALSE){
		$msg = "Error: ".mysql_error()." while executing:\n".$q;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		mysql_close($mysql);
		exit(1);
		}
	$cl_description = mysql_result($r, 0, "description");

	$q = "SELECT cluster.id, cluster.description
				FROM client_cluster
				LEFT JOIN cluster ON client_cluster.cluster_id = cluster.id
				WHERE client_cluster.client_id = '$client_id'";
	$r = mysql_query($q);
	if($r == FALSE){
		$msg = "Error: ".mysql_error()." while executing:\n".$q;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		mysql_close($mysql);
		exit(1);
		}
?>      <table>
        <tr>
          <th><? echo $cl_description;?></th>
				</tr>
<?
	if(mysql_num_rows($r) == 1){
		$bc_description = mysql_result($r, 0, "cluster.description");
		$cluster_id = mysql_result($r, 0, "cluster.id");
?>
				<tr>
					<th onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_ll.php?cluster_id=<? echo $cluster_id; ?>"><? echo $bc_description;?></a></th>
				</tr>
<?
		}
?>				
        <tr>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_client.php?client_id=<?echo $client_id?>">╤нформац╕я про кл╕╓нта</a></td>
        </tr>
        <tr>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_properties.php?client_id=<?echo $client_id?>">Рекв╕зити кл╕╓нта</a></td>
        </tr>
<?
 $dfrom = mktime(0, 0, 0, date("m"), 1, date("Y"));
 $dto = mktime();
 
?>
        <tr>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_traffic.php?client_id=<? echo $client_id?>&amp;dbegin=<? echo $dfrom?>&amp;dend=<? echo $dto?>">Зв╕ти про траф╕к</a></td>
        </tr>
        <tr>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="reportpersonal.php?client_id=<? echo $client_id?>&amp;dbegin=<? echo $dfrom?>&amp;dend=<? echo $dto?>">Траф╕к по дням</a></td>
        </tr>
        <tr>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_contract.php?client_id=<?echo $client_id?>">Договори</a></td>
        </tr>
        <tr>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_service.php?client_id=<?echo $client_id?>">Послуги</a></td>
        </tr>
        <tr>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_charges.php?client_id=<?echo $client_id?>">Нарахування</a></td>
        </tr>
        <tr>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_payment.php?client_id=<?echo $client_id?>">Платеж╕</a></td>
        </tr>
        <tr>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_bill.php?client_id=<?echo $client_id?>">Рахунки</a></td>
        </tr>
        <tr>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_stwa.php?client_id=<?echo $client_id?>">Акти виконаних роб╕т</a></td>
        </tr>
        <tr>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_account.php?client_id=<?echo $client_id?>">Особовий рахунок</a></td>
        </tr>
      </table>
