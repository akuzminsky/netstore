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
  $query = "
		SELECT
		contract.id,
		contract.c_type,
		contract.c_number,
		SUM(charge.value) AS charged
		FROM contract
		LEFT JOIN service ON service.contract_id = contract.id
		LEFT JOIN charge ON charge.service_id = service.id
		WHERE contract.client_id = '$client_id'
		GROUP BY contract.id";
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
					<caption>Стан рахунку</caption>
            <tr bgcolor="lightgrey">
              <th>Номер Договору</th>
              <th>Нараховано</th>
              <th>Перераховано</th>
              <th>Баланс</th>
            </tr>
<?
	$whole_balance = 0;
  for($i = 0; $i < $n; $i++){
	$contract_id = mysql_escape_string(mysql_result($result, $i, "contract.id"));
	$c_type = mysql_result($result, $i, "contract.c_type");
	$c_number = mysql_result($result, $i, "contract.c_number");
	$charged = mysql_result($result, $i, "charged");
?>
            <tr bgcolor="white">
              <td><? echo $c_type." - ".$c_number;?></td>
              <td align="right"><? echo number_format($charged, 2, ".", "");?></td>
<?
	$q = "SELECT
		SUM(payment.value) AS paid
		FROM payment
		WHERE payment.contract_id = '$contract_id'";
	$r = mysql_query($q);
	if($r == FALSE){
		$msg = "Error: ".mysql_error()." while executing ".$query;
		header ("Location: show_error.php?error=".$msg);
		mysql_close($mysql);
		exit(1);
		}
	if(mysql_num_rows($r) == 1){
		$paid = mysql_result($r, 0, "paid");
		}
	else{
		$paid = 0;
		}
	mysql_free_result($r);
	$balance = $paid - $charged;
	$whole_balance += $balance;
?>
              <td align="right"><? echo number_format($paid, 2, ".", "");?></td>
              <td align="right"><?if($balance < 0){ echo "<font color=\"red\">"; }?><? echo number_format($balance, 2, ".", "");?><?if($balance < 0){ echo "</font>"; }?></td>
            </tr>
<?
    }
?>				<tr bgcolor="white">
						<td colspan="3">
						<td align="right"><?if($whole_balance < 0){ echo "<font color=\"red\">"; }?><? echo number_format($whole_balance, 2, ".", "");?><?if($whole_balance < 0){ echo "</font>"; }?></td>
					</tr>
          </table>
        </td>
      </tr>
<?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
