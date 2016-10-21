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
    session_timeout();
		}
  if(FALSE == mysql_select_db($db)){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $client_id = mysql_escape_string($_GET["client_id"]);
  $action = mysql_escape_string($_GET["action"]);
	$stwa_id = mysql_escape_string($_GET["stwa_id"]);
	if($action == "delete"){
		delete_stwa($mysql, $stwa_id);
		}
	$query = "select client.id,
    client.description,
    client.manager_id,
    client.person,
    client.phone,
    client.email,
    client.port,
    client.notes
    from client
    where client.id = '$client_id'";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $responsible_manager = mysql_result($result, 0, "client.manager_id");
  if($permlevel == 'manager'){
    $readonlystatus = "readonly";
    if($login != $responsible_manager){
      header($start_url);
      }
    }
  if($responsible_manager == ''){
    $responsible_manager = 'Нет';
    }
  $query = "SELECT
      stwa.id,
      stwa.stwa_num,
      stwa.contract_id,
      UNIX_TIMESTAMP(stwa.create_time) AS create_timestamp,
      UNIX_TIMESTAMP(stwa.starttime) AS starttime,
      UNIX_TIMESTAMP(stwa.stoptime) AS stoptime,
      stwa.value_without_vat,
      contract.client_id,
      contract.c_type,
      contract.c_number,
      contract.description
      FROM contract
			LEFT JOIN stwa ON contract.id = stwa.contract_id
      where contract.client_id = '$client_id'
			HAVING stwa.id IS NOT NULL
      ORDER BY create_timestamp desc";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  log_event($mysql, $client_id, "stwa", "", "view", "Get STWA list of the client id $client_id");
	$n = mysql_num_rows($result);
?>
      <tr>
				<td valign="top" width="20%"><? include("left_cl.php"); ?></td>
        <td valign="top">
          <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver" title="Акти виконаних роб╕т">
          <caption>Акти виконаних роб╕т</caption>
            <tr bgcolor="lightgrey">
              <th>Номер</th>
              <th>Пер╕од</th>
              <th>Дата створення</th>
              <th>Номер договору</th>
              <th>Сума без ПДВ</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
            </tr>
<?
  for($i = 0; $i < $n; $i++){
    $stwa_id = mysql_result($result, $i, "stwa.id");
    $stwa_num = mysql_result($result, $i, "stwa.stwa_num");
    $stwa_create_timestamp = strftime("%d %B %Y", mysql_result($result, $i, "create_timestamp"));
    $stwa_starttime = strftime("%d %B %Y", mysql_result($result, $i, "starttime"));
    $stwa_stoptime = strftime("%d %B %Y", mysql_result($result, $i, "stoptime"));
    $contract_c_type = mysql_result($result, $i, "contract.c_type");
    $contract_c_number = mysql_result($result, $i, "contract.c_number");
    $contract_description = mysql_result($result, $i, "contract.description");
    $value_without_vat = mysql_result($result, $i, "stwa.value_without_vat");
?>
            <tr bgcolor="white" onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'">
              <td><? printf("%08u", $stwa_num);?></td>
              <td><? echo $stwa_starttime."-".$stwa_stoptime;?></td>
              <td><? echo $stwa_create_timestamp;?></td>
              <td><? echo $contract_c_type."-".$contract_c_number;?></td>
              <td align="right"><? echo number_format($value_without_vat, 2, ".", " ");?></td>
              <td><a href="print_stwa.php?stwa_id=<? echo $stwa_id;?>" target="_new">друк</a></td>
              <td><a href="show_stwa.php?client_id=<? echo $client_id;?>&amp;stwa_id=<? echo $stwa_id;?>&amp;action=delete">видалити</a></td>
						</tr>
<?
    }
?>
            <tr bgcolor="white">
              <td colspan="7" align="right"><a href="add_stwa.php?client_id=<? echo $client_id?>">Створити новий акт</a></td>
            </tr>
          </table>
        </td>
      </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
