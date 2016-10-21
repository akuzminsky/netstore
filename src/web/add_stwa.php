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
    $msg = mysql_error();
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  $REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
  if($REQUEST_METHOD == "GET"){
    $client_id = mysql_escape_string($_GET["client_id"]);
    }
  if($REQUEST_METHOD == "POST"){
    $client_id = mysql_escape_string($_POST["client_id"]);
    }
  $query = "SELECT client.id,
    client.description,
    client.manager_id,
    client.person,
    client.phone,
    client.email,
    client.port,
    client.notes
    FROM client
    WHERE client.id = '$client_id'";
  $result = mysql_query($query);
  if($result == FALSE){
    $msg = "Error: ".mysql_error()." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
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
  if(!empty($_POST["create"])){
    $contract_id = mysql_escape_string($_POST["contract_id"]);
    $y = mysql_escape_string($_POST["year"]);
    $m = mysql_escape_string($_POST["month"]);
    add_stwa($mysql, $contract_id, $y, $m);
		log_event($mysql, $client_id, "stwa", "", "add", "Created stwa for contract id $contract_id, period $y/$m");
		header("Location: show_stwa.php?client_id=".$client_id);
		exit(0);
    } // end of "create"
  $query = "select
      id,
      c_type,
      c_number,
      description
      from contract
      where client_id = '$client_id'
			AND (expire_time = 0 OR expire_time > NOW())";
  $result = mysql_query($query);
  if($result == FALSE){
    $msg = "Error: ".mysql_error()." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($result);
	if($n == 0){
		header("Location: show_contract.php?client_id=".$client_id);
		exit(0);
		}
?>
    <tr>
      <td valign="top" width="20%">
<? include("left_cl.php"); ?>
      </td>
      <td valign="top">
        <form method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
        <input type="hidden" name="client_id" value="<? echo $client_id; ?>">
        <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
        <caption>Вибер╕ть Догов╕р для акту виконаних роб╕т та пер╕од</caption>
          <tr bgcolor="white">
            <td>
              <select name="contract_id">
<?
  for($i = 0; $i < $n; $i++){
    $id = mysql_result($result, $i, "id");
    $c_type = mysql_result($result, $i, "c_type");
    $c_number = mysql_result($result, $i, "c_number");
    $description = mysql_result($result, $i, "description");
?>
                <option value="<? echo $id?>"><? echo $c_type." - ".$c_number.": ".$description?></option>
<?
    }
?>
              </select>
            </td>
            <td>
              <table>
                <tr>
                  <td>
                    <select name="year">
<?
  $today = getdate();
  $y1 = $today['year'];
  $m1 = $today['mon'];
  for($i = $y1 - 3; $i < $y1 + 2; $i++){
    if($y1 == $i) $s = "selected"; else $s = "";
?>
                      <option value="<?echo $i?>"<? if($s != "") echo " $s"; ?>><? echo $i?></option>
<?
    }
?>
                    </select>
                  </td>
                  <td>
                    <select name="month">
<?
  for($i = 1; $i <= 12; $i++){
    if($i == $m1 - 1) $s = "selected"; else $s = "";
?>
                      <option value="<? printf("%02d", $i);?>"<? if($s != "") echo " $s"; ?>><? echo strftime("%OB",mktime(0,0,0,$i,1,date("Y")))?></option>
<?
    }
?>
                    </select>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr bgcolor="white">
            <td colspan="2" align="right"><input type="submit" name="create" value="Створити..."></td>
          </tr>
        </table>
        </form>
      </td>
    </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
