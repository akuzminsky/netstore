<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  readfile("$DOCUMENT_ROOT/head.html");
  include("$DOCUMENT_ROOT/netstorecore.php");
  session_start();
  if(!@session_is_registered('authdata')){
    header($start_url);
    }
  if($_SESSION['authdata']['permlevel'] != 'admin' 
      && $_SESSION['authdata']['permlevel'] != 'manager'
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
    header ("Location: show_error.php?error=".mysql_error());
    exit(1);
    }
  if(FALSE == mysql_select_db($db)){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
	if(!empty($_POST["save"])){
		$num = $_POST["num"];
		mysql_query("begin");
		for($i = 0; $i < $num; $i++){
			$contract_id = $_POST["contract_id".$i];
			if($_POST["saldo".$i] != ""){
				$saldo = $_POST["saldo".$i];
				$value_without_vat = round2($saldo/1.2);
				$vat = round2($saldo - $value_without_vat);
				$query = "insert into payment( contract_id,  timestamp , value, operator, notice,  value_without_vat, vat)
			values('$contract_id', '2004-03-31', '$saldo', 'ingoth', 'корегуючий плат╕ж', '$value_without_vat', '$vat')";
				mysql_query($query);
				echo $query."<br>";
				}
			}
		mysql_query("commit");
    header ("Location: saldo.php?sid=".session_id());
		exit(1);
		}
	$query = "select 
			contract.id,
			client.id,
			client.description,
			payment.id,
			sum(if(payment.timestamp = '2004-03-31 00:00:00', 1, 0)) as num
			from contract
			left join client on client.id = contract.client_id
			left join payment on payment.contract_id = contract.id
			group by client.id
			having num = 0
			order by client.description";
	//echo $query;
	//exit(0);
	$result = mysql_query($query);
	$n = mysql_num_rows($result);
	$color1 = "lightyellow";
	$color2 = "#EfEfEf";
?>
	<form method="POST">
	<table border="1">
<?
	for($i = 0; $i < $n; $i++){
		$client_id = mysql_result($result, $i, "client.id");
		$description = mysql_result($result, $i, "client.description");
		$contract_id = mysql_result($result, $i, "contract.id");
?>
		<tr bgcolor="<? $color = ($color == $color1) ? $color2 : $color1; echo $color?>">
			<td><a href="show_client.php?client_id=<?echo $client_id?>"><?echo $description?></a></td>
			<td><input type="text" name="saldo<?echo $i?>"><input type="hidden" name="contract_id<?echo $i?>" value="<? echo $contract_id?>"></td>
		</tr>

<?
		}

?>
	<tr>
		<td colspan="2"><input type="submit" name="save" value="Зберегти">
		<input type="hidden" name="num" value="<? echo $n?>"></td></tr>
	</table>
	</form>
<?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
