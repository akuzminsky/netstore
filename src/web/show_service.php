<?
  $DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
  readfile("$DOCUMENT_ROOT/head.html");
  include("$DOCUMENT_ROOT/netstorecore.php");
  session_start();
  if(!@session_is_registered("authdata")){
    header($start_url);
    }
  if($_SESSION["authdata"]["permlevel"] != "admin"
	  && $_SESSION["authdata"]["permlevel"] != "manager"
      && $_SESSION["authdata"]["permlevel"] != "topmanager"){
    header($start_url);
    }
 
  $authdata = $_SESSION["authdata"];
  include("top.php");
  $login = $authdata["login"];
  $passwd = $authdata["passwd"];
  $permlevel = $authdata["permlevel"];
  $mysql = @mysql_connect($host, $login, $passwd); mysql_set_charset("koi8u");
  if($mysql == FALSE){
    session_timeout();
    }
  if(FALSE == mysql_select_db($db)){
    header ("Location: show_error.php?error=".mysql_error());
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
  if($permlevel == "manager"){
    $readonlystatus = "readonly";
    if($login != $responsible_manager){
      header($start_url);
      exit(0);
      }
    }
  if($responsible_manager == ""){
    $responsible_manager = "Нет";
    }
  if(!empty($_POST["save"])){
    if(mysql_escape_string($_POST["fullupdate"]) == "on"){
      $fullupdate = "-f";
      }
    for($i = 0; $i < $_POST[num]; $i++){
      mysql_query("BEGIN");
      $id = mysql_escape_string($_POST["id".$i]);
      $contract_id = mysql_escape_string($_POST["contract_id".$i]);
      $description = mysql_escape_string($_POST["description".$i]);
      $short_description = mysql_escape_string($_POST["short_description".$i]);
      if($description == ""){
        $description = mysql_escape_string($_POST["sel_description".$i]);
        }
      $service_type_id = mysql_escape_string($_POST["service_type_id".$i]);
      $start_time = $_POST["start_time".$i."y1"]."-".$_POST["start_time".$i."m1"]."-".$_POST["start_time".$i."d1"]." 00:00:00";
      $start_time = mysql_escape_string($start_time);
      $expire_time = $_POST["expire_time".$i."y1"]."-".$_POST["expire_time".$i."m1"]."-".$_POST["expire_time".$i."d1"]." 00:00:00";
      $expire_time = mysql_escape_string($expire_time);
      $cash = mysql_escape_string($_POST["cash".$i]);
      $main_currency = mysql_escape_string($_POST["main_currency".$i]);
      $query = "UPDATE service
          SET contract_id = '$contract_id', 
          description = '$description',
          short_description = '$short_description',
          service_type_id = '$service_type_id',
          start_time = '$start_time',
          expire_time = '$expire_time',
          cash = '$cash'
          WHERE id = $id";
      if(FALSE == mysql_query($query)){
        header ("Location: show_error.php?error=".mysql_error());
        mysql_query("ROLLBACK");
        mysql_close($mysql);
        exit(1);
        }
      $tariff_id = mysql_escape_string($_POST["tariff_id".$i]);
      $tariff = mysql_escape_string($_POST["tariff".$i]);
      $monthlypayment = mysql_escape_string($_POST["monthlypayment".$i]);
      $query = "UPDATE tariff
          SET tariff = '$tariff',
          main_currency = '$main_currency',
          monthlypayment = '$monthlypayment'
          WHERE id = $tariff_id";
      if(FALSE == mysql_query($query)){
        header ("Location: show_error.php?error=".mysql_error());
        mysql_query("ROLLBACK");
        mysql_close($mysql);
        exit(1);
        }
      mysql_query("COMMIT");
      $cmd = $bin_dir."/charge -u ".$login." -p ".$passwd." -d ".$db." -s ".$id." ".$fullupdate;
      $r = 0;
      exec($cmd, $out, $r);
      //passthru($cmd);
      echo "r = $r";
      if($r != 0){
        $err = $cmd.": Error occurs while parsing tariff(return code $r)<br>";
        $err .= "PATH: ".$_SERVER["PATH"]."<br>";
        foreach($out as $val){
          $err .= $val."<br>";
          }
        }
      }
    $client_id = mysql_escape_string($_POST["client_id"]);
    header("Location: show_service.php?client_id=".$client_id."&amp;"."&amp;err=".$err);
    exit(0);
    }
  if(!empty($_GET["action"]) && $_GET["action"] == "add"){
    mysql_query("BEGIN");
    $query = "INSERT INTO tariff(tariff) VALUES('')";
    if(FALSE == mysql_query($query)){
      header ("Location: show_error.php?error=".mysql_error());
      mysql_query("ROLLBACK");
      mysql_close($mysql);
      exit(1);
      }
    $tariff_id = mysql_insert_id($mysql);
    $contract_id = mysql_escape_string($_GET["contract_id"]);
    $query = "INSERT INTO service(contract_id, tariff_id) VALUES($contract_id, $tariff_id)";
    if(FALSE == mysql_query($query)){
      header ("Location: show_error.php?error=".mysql_error());
      mysql_query("ROLLBACK");
      mysql_close($mysql);
      exit(1);
      }
    mysql_query("COMMIT");
    }
  if(!empty($_GET["action"]) && $_GET["action"] == "delete"){
    $id = mysql_escape_string($_GET["id"]);
    delete_service($mysql, $id, "yes");
    }
  $client_id = mysql_escape_string($_GET["client_id"]);
  if($_GET["err"] != ""){
    header ("Location: show_error.php?error=".$_GET["err"]);
    exit(0);
    }
?>
  <tr>
    <td valign="top" width="20%">
<? include("left_cl.php"); ?>
    </td>
    <td>
      <table border="0" width="100%">
        <tr>
          <td>
            <table border="0">
            <caption>Дов╕дка по ф╕льтрам</caption>
<?
  $q = "select id, description from filter where client_id = $client_id";
  $r= mysql_query($q);
  if($r== FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($r);
  for($i = 0; $i < $n; $i++){
?>
              <tr>
                <td>filter(<b><? echo mysql_result($r, $i, "id")?></b>)</td>
                <td><? echo mysql_result($r, $i, "description")?></td>
              </tr>
<?
    }
  $q = "select max(id) as mid from contract where client_id = $client_id having mid is not null";
  $r= mysql_query($q);
   if($r== FALSE){
     header ("Location: show_error.php?error=".mysql_error());
     mysql_close($mysql);
     exit(1);
     }
   if(mysql_num_rows($r) == 1){
     $contract_id = mysql_result($r, 0, "mid");
     $addlink = "show_service.php?client_id=$client_id&amp;action=add&amp;contract_id=$contract_id";
     }
   else{
     $addlink = "show_contract.php?client_id=$client_id";
     }
?>
              <tr>
                <td>traffic(<b><? echo $client_id?></b>)</td>
                <td>- Траф╕к кл╕╓нта</td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td><a href="<? echo $addlink;?>">Додати нову послугу</a></td>
        </tr>
        <tr>
          <td>
            <form method="POST" action="<? echo $_SERVER["PHP_SELF"]?>">
            <table width="100%" cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
<?
  $query = "select 
      service.id,
      service.description,
      service.short_description,
      service.contract_id,
      service.service_type_id,
      DAYOFMONTH(service.start_time) as start_d,
      MONTH(service.start_time) as start_m,
      YEAR(service.start_time) as start_y,
      DAYOFMONTH(service.expire_time) as expire_d,
      MONTH(service.expire_time) as expire_m,
      YEAR(service.expire_time) as expire_y,
      service.expire_time <> 0 AND service.expire_time < NOW() AS active_now,
      service.tariff_id,
      service.cash,
      tariff.monthlypayment,
      tariff.main_currency,
      tariff.tariff
      from service
      left join contract on contract.id = service.contract_id
      left join tariff on tariff.id = service.tariff_id
      where contract.client_id = $client_id";
  $result = mysql_query($query);
   if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($result);
  for($i = 0; $i < $n; $i++){
    $len = strlen(mysql_result($result, $i, "description"));
    $active_now = mysql_result($result, $i, "active_now");
    $short_description = mysql_result($result, $i, "short_description");
?>
              <tr bgcolor="lightgrey">
                <th>Послуга</th>
                <td colspan="1">
                  <select name="sel_description<?echo $i?>">
<?
  $q = "SELECT DISTINCT description FROM service WHERE description <> '---' ORDER BY description";
  $r = mysql_query($q);
  if($r == FALSE){
    $msg = "Error: ".mysql_error()." while executing:\n".$q;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);                                                
    }
  echo "<option value=\"---\">---</option>\n";
  $is_selected = "";
  for($k = 0; $k < mysql_num_rows($r); $k++){
    $d = mysql_result($r, $k, "description");
    if($d == mysql_result($result, $i, "description")){
      $is_selected = "selected";
      }
    else{
      $is_selected = "";
      }
    echo "<option value=\"".$d."\" $is_selected>".$d."</option>\n";
    }
?>
                  </select>
                  <br>
                  <input type="text" size="<? echo $len + 2; ?>" name="description<?echo $i?>">
                  <br>
                  Коротка назва<input type="text" size="<? echo $len + 2; ?>" name="short_description<?echo $i?>" value="<? echo $short_description; ?>"></td>
              </tr>
              <tr bgcolor="<? if($active_now == 1){ echo "lightgrey"; } else { echo "white"; }?>">
                <td>
                  <table border="0">
                    <tr>
                      <td>Догов╕р</td>
                    </tr>
                    <tr>
                      <td>
                        <select name="contract_id<?echo $i?>">
<?
  $q = "select id, c_type, c_number, description from contract where client_id = $client_id";
  $r = mysql_query($q);
  if($r == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $k = mysql_num_rows($r);
  if($k == 0){
    header("Location: show_contract.php?client_id=".$client_id);
    mysql_close($mysql);
    exit(0);
    }
  for($j = 0; $j < $k; $j++){
?>
                          <option value="<? echo mysql_result($r, $j, "id");?>" <? if(mysql_result($r, $j, "id") == mysql_result($result, $i, "contract_id")) { echo "selected"; }?>><? echo mysql_result($r, $j, "c_type")."-".mysql_result($r, $j, "c_number").":".mysql_result($r, $j, "description")?></option>
<?
    }
?>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td>Тип послуги</td>
                    </tr>
                    <tr>
                      <td>
                        <select name="service_type_id<?echo $i?>">
<?
  $q = "select id, service_type from service_type";
  $r = mysql_query($q);
  if($r == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $k = mysql_num_rows($r);
  for($j = 0; $j < $k; $j++){
?>
                          <option value="<? echo mysql_result($r, $j, "id");?>" <? if(mysql_result($r, $j, "id") == mysql_result($result, $i, "service_type_id")) { echo "selected"; }?>><? echo mysql_result($r, $j, "service_type");?></option>
<?
    }
?>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                    </tr>
                    <tr>
                      <td>Дата початку(включно)</td>
                    </tr>
                    <tr>
                      <td>
<?
  $start_time['mday'] = mysql_result($result, $i, "start_d");
  $start_time['mon'] = mysql_result($result, $i, "start_m");
  $start_time['year'] = mysql_result($result, $i, "start_y");
  if($start_time['mday'] == 0){
    showdate("start_time".$i, getdate());
    }
  else{
    showdate("start_time".$i, $start_time);
    }
?>
                      </td>
                    </tr>
                    <tr>
                      <td>Дата зак╕нчення(не включно)</td>
                    </tr>
                    <tr>
                      <td>
<?
  $expire_time['mday'] = mysql_result($result, $i, "expire_d");
  $expire_time['mon'] = mysql_result($result, $i, "expire_m");
  $expire_time['year'] = mysql_result($result, $i, "expire_y");
  showdate("expire_time".$i, $expire_time);
  $y_checked = mysql_result($result, $i, "tariff.monthlypayment") == "yes" ? "checked" : "";
  $n_checked = mysql_result($result, $i, "tariff.monthlypayment") == "no" ? "checked" : "";
  $y_cash_checked = mysql_result($result, $i, "service.cash") == "yes" ? "checked" : "";
  $n_cash_checked = mysql_result($result, $i, "service.cash") == "no" ? "checked" : "";
  $y_main_currency_checked = mysql_result($result, $i, "tariff.main_currency") == "yes" ? "checked" : "";
  $n_main_currency_checked = mysql_result($result, $i, "tariff.main_currency") == "no" ? "checked" : "";
?>
                      </td>
                    </tr>
                  </table>
                </td>
                <td>
                  <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
                    <tr>
                      <th colspan="3">Тариф</th>
                    </tr>
                    <tr bgcolor="<? if($active_now == 1){ echo "lightgrey"; } else { echo "white"; }?>">
                      <td>
                        <table>
                          <tr>
                            <td rowspan="3">За послугу нарахову╓ться передплата</td>
                          </tr>
                          <tr>
                            <td>
                              <input type="radio" name="monthlypayment<?echo $i?>" value="yes" id="yes_monthlypayment<?echo $i?>_id" <? echo $y_checked?>><label for="yes_monthlypayment<?echo $i?>_id">Так</label>
                            </td>
                          </tr>
                          <tr>
                            <td>
                              <input type="radio" name="monthlypayment<?echo $i?>" value="no" id="no_monthlypayment<?echo $i?>_id" <? echo $n_checked?>><label for="no_monthlypayment<?echo $i?>_id">Н╕</label>
                            </td>
                          </tr>
                        </table>
                      </td>
                      <td>
                        <table>
                          <tr>
                            <td rowspan="3">╤нша послуга</td>
                          </tr>
                          <tr>
                            <td>
                              <input type="radio" name="cash<?echo $i?>" value="yes" id="yes_cash<?echo $i?>_id" <? echo $y_cash_checked?>><label for="yes_cash<?echo $i?>_id">Так</label>
                            </td>
                          </tr>
                          <tr>
                            <td>
                              <input type="radio" name="cash<?echo $i?>" value="no" id="no_cash<?echo $i?>_id" <? echo $n_cash_checked?>><label for="no_cash<?echo $i?>_id">Н╕</label>
                            </td>
                          </tr>
                        </table>
                      </td>
                      <td>
                        <table>
                          <tr>
                            <td rowspan="3">Тариф у гривнях</td>
                          </tr>
                          <tr>
                            <td>
                              <input type="radio" name="main_currency<?echo $i?>" value="yes" id="yes_main_currency<?echo $i?>_id" <? echo $y_main_currency_checked; ?>><label for="yes_main_currency<?echo $i?>_id">Так</label>
                            </td>
                          </tr>
                          <tr>
                            <td>
                              <input type="radio" name="main_currency<?echo $i?>" value="no" id="no_main_currency<?echo $i?>_id" <? echo $n_main_currency_checked; ?>><label for="no_main_currency<?echo $i?>_id">Н╕</label>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    <tr bgcolor="<? if($active_now == 1){ echo "lightgrey"; } else { echo "white"; }?>">
                      <td colspan="3">
                        <textarea name="tariff<?echo $i?>" rows="10" cols="80"><? echo mysql_result($result, $i, "tariff.tariff")?></textarea>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr bgcolor="white">
                <td colspan="2" align="right"><a href="show_service.php?client_id=<? echo $client_id?>&amp;action=delete&amp;id=<? echo mysql_result($result, $i, "id")?>">Видалити послугу</a>
                  <input type="hidden" name="tariff_id<?echo $i?>" value="<? echo mysql_result($result, $i, "tariff_id")?>">
                  <input type="hidden" name="id<?echo $i?>" value="<? echo mysql_result($result, $i, "id")?>">
                </td>
              </tr>
<?
    }
?>            <tr bgcolor="white">
                <td colspan="2" align="right">
                  <table>
                    <tr>
                      <td><input type="checkbox" name="fullupdate" ></td>
                      <td>Перерахувати суми по всьому пер╕оду</td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr bgcolor="white">
                <td colspan="2" align="right">
                  <input type="submit" name="save" value="Зберегти">
                  <input type="hidden" name="num" value="<?echo $n?>">
                  <input type="hidden" name="client_id" value="<?echo $client_id?>">
                </td>
              </tr>
            </table>
            </form>
         </td>
      </tr>
    </table>
  </td>

<?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
