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
	$client_id = mysql_escape_string($_GET[client_id]);
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
	if(!empty($_POST[save])){
		mysql_query("BEGIN");
		for($i = 0; $i < $_POST[num]; $i++){
			$id = $_POST["id".$i];
			$c_number = mysql_escape_string($_POST["c_number".$i]);
			$c_type = mysql_escape_string($_POST["c_type".$i]);
			$description = mysql_escape_string($_POST["description".$i]);
			$start_time = mysql_escape_string($_POST["start_time".$i]);
			$expire_time = mysql_escape_string($_POST["expire_time".$i]);
			$query = "UPDATE contract 
					SET c_number = '$c_number',
					c_type = '$c_type',
					description = '$description',
					start_time = '$start_time',
					expire_time = '$expire_time'
					WHERE id = $id";
			if(FALSE == mysql_query($query)){
				header ("Location: show_error.php?error=".mysql_error());
				mysql_query("ROLLBACK");
				mysql_close($mysql);
				exit(1);
				}
			}
		mysql_query("COMMIT");
		$client_id = $_POST[client_id];
		header("Location: show_contract.php?client_id=".$client_id);
		exit(0);
		}
	if(!empty($_GET[action]) && $_GET[action] == 'add'){
		$client_id = mysql_escape_string($_GET[client_id]);
		$query = "INSERT INTO contract(client_id) VALUES($client_id)";
		if(FALSE == mysql_query($query)){
			header ("Location: show_error.php?error=".mysql_error());
			mysql_query("ROLLBACK");
			mysql_close($mysql);
			exit(1);
			}
		}
	if(!empty($_GET["action"]) && $_GET["action"] == "delete"){
		$id = mysql_escape_string($_GET["id"]);
		delete_contract($mysql, $id, "yes");
		}
  $client_id = mysql_escape_string($_GET[client_id]);
?>
  <tr>
		<td valign="top" width="20%">
<? include("left_cl.php"); ?>
		</td>
    <form method="POST" action="<? echo $_SERVER["PHP_SELF"];?>">
    <td align="center" valign="top">
      <table width="100%" cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
        <tr bgcolor="lightgrey">
          <th><a href="show_contract.php?client_id=<? echo $client_id?>&action=add">+</a></th>
          <th>Тип Договора</th>
          <th>Номер Договора</th>
          <th>Назва</th>
          <th>Дата початку д╕╖ Договора</th>
          <th>Дата зак╕нчення д╕╖ Договора</th>
        </tr>
<?
	$query = "select 
      id, 
      c_number, 
      c_type, 
      description, 
      start_time, 
      expire_time 
      from contract where client_id = $client_id";
  $result = mysql_query($query);
   if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($result);
  for($i = 0; $i < $n; $i++){
?>
        <tr bgcolor="white">
          <td><a href="show_contract.php?client_id=<? echo $client_id?>&action=delete&id=<? echo mysql_result($result, $i, "id")?>">x</a></td>
          <td><input type="text" name="c_type<?echo $i?>" value="<? echo mysql_result($result, $i, "c_type")?>"></td>
          <td><input type="text" name="c_number<?echo $i?>" value="<? echo mysql_result($result, $i, "c_number")?>"></td>
          <td><input type="text" name="description<?echo $i?>" value="<? echo mysql_result($result, $i, "description")?>"></td>
          <td><input type="text" name="start_time<?echo $i?>" value="<? echo mysql_result($result, $i, "start_time")?>"></td>
          <td><input type="text" name="expire_time<?echo $i?>" value="<? echo mysql_result($result, $i, "expire_time")?>"></td>
        	<input type="hidden" name="id<?echo $i?>" value="<? echo mysql_result($result, $i, "id")?>">
				</tr>
<?
    }
?>      <tr bgcolor="white">
          <input type="hidden" name="num" value="<?echo $n?>">
          <input type="hidden" name="client_id" value="<?echo $client_id?>">
          <td colspan="6" align="center"><input type="submit" name="save" value="Зберегти"></td>
        </tr>
      </table>
    </td>
    </form>
  </tr>
<?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
