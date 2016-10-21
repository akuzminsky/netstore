<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  readfile("$DOCUMENT_ROOT/head.html");
  session_start();
  if(!@session_is_registered('authdata') || $_SESSION['authdata']['permlevel'] != 'admin'){
    header($start_url);
    }
  if($_SESSION['authdata']['permlevel'] != 'admin'){
    header($start_url);
    } 
  $authdata = $_SESSION['authdata'];
  include("$DOCUMENT_ROOT/netstorecore.php");
  include("top.php");
  $login = $authdata['login'];
  $passwd = $authdata['passwd'];
  $mysql = @mysql_connect($host, $login, $passwd); mysql_set_charset("koi8u");
  if($mysql == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    exit(1);
    }
  if(FALSE == mysql_select_db($db)){
    header ("Location: show_error.php?error=".mysql_error($mysql));
    mysql_close($mysql);
    exit(1);
    }
  if(empty($_POST)){
?>
  <tr>
    <td valign="top" align="center" colspan="2">
      <form action="<? echo $_SERVER['PHP_SELF']; ?>" method="POST" name="addUserForm" onsubmit="return checkAddUser()" onreset="document.forms['addUserForm'].elements['cl_login'].focus();">
      <table border="0">
        <tr>
          <th colspan="2">Логин</th>
          <td>
            <input type="text" name="cl_login" onblur="document.forms['addUserForm'].elements['cl_description'].focus();"></td>
            <input type="hidden" name="cluster_id" value="<? if($_GET["cluster_id"] != ""){ echo $_GET["cluster_id"];} else { echo "0"; }?>"></td>
        </tr>
        <tr>
          <td>
            <input type="radio" name="nopass" value="1" id="radio_nopass1" onclick="pw1.value = ''; pw2.value = ''; this.checked = true; document.forms['addUserForm'].elements['cl_description'].focus();" checked="checked">
            <label for="radio_nopass1">Сгенерировать</label>
          </td>
        </tr>
        <tr>
          <td rowspan="2">
            <input type="radio" name="nopass" value="0" id="radio_nopass0" onclick="document.forms['addUserForm'].elements['pw1'].focus();">
            <label for="radio_nopass0">Указать явно</label>
          </td>
          <th align="right">Пароль</th>
          <td>
            <input type="password" name="pw1" onpropertychange="nopass[1].checked = true">
        </tr>
        <tr>
          <th align="right">Подтверждение пароля</th>
          <td>
            <input type="password" name="pw2" onpropertychange="nopass[1].checked = true">
          </td>
        <tr>
          <th colspan="2" align="right">Название клиента</th>
          <td>
            <input type="text" name="cl_description" value="<? echo $_GET["cl_description"]?>">
          </td>
        </tr>
        <tr>
          <th colspan="2" align="right">Контактное лицо</th>
          <td>
            <input type="text" name="cl_person" value="<? echo $_GET["cl_person"]?>">
          </td>
        <tr>
          <th colspan="2" align="right">Телефон</th>
          <td>
            <input type="text" name="cl_phone" value="<? echo $_GET["cl_phone"]?>">
          </td>
        <tr>
          <th colspan="2" align="right">Адрес электронной почты</th>
          <td>
            <input type="text" name="cl_email" onblur="if(sendemail.checked==true){email2send.value = cl_email.value;}" value="<? echo $_GET["cl_email"]?>">
          </td>
        <tr>
          <td colspan="3" align="center">
            <table border="0">
              <tr>
                <th rowspan="3">Карточку клиента:</th>
                <td>
                  <input type="checkbox" name="2newwindow" id="checkbox_2newwindow" checked="checked">
                  <label for="checkbox_2newwindow">Показать в окне</label>
                </td>
              </tr>
              <tr>
                <td rowspan="1">
                  <input type="checkbox" name="sendemail" id="checkbox_sendemail" checked="checked" onpropertychange="email2send.value = ''">
                  <label for="checkbox_sendemail">Отправить по электронной почте</label>
              <tr>
                <td>
                  <input type="text" name="email2send" onfocus="sendemail.checked=true">
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <table border="0">
              <tr>
                <th>
                  <input type="checkbox" name="createmailbox" checked id="checkbox_createmailbox">
                  <label for="checkbox_createmailbox">Создать почтовый ящик</label>
                </th>
                <td>
                  <input type="text" name="newmailbox">
                </td>
              </tr>
              <tr>
                <td>Логин администратора</td>
                <td>
                  <input type="text" name="admin_login" value="<? echo $login?>">
                </td>
              </tr>
              <tr>
                <td>Пароль администратора</td>
                <td>
                  <input type="password" name="admin_passwd" value="<? echo $passwd?>">
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <th colspan="3">
            <input type="submit" value="Добавить">
          </th>
        </tr>
      </table>
      </form>
    </td>
  </tr>
<?
    }
  else{
    if($_POST["pw1"] != $_POST["pw2"]){
      header ("Location: show_error.php?error=Парол╕ не сп╕впадають");
      exit(0);
      }
    else{
      // Put record in the table userlevel
      $cl_login = mysql_escape_string($_POST["cl_login"]);
      $cl_description = mysql_escape_string($_POST["cl_description"]);
      $cl_person = mysql_escape_string($_POST["cl_person"]);
      $cl_phone = mysql_escape_string($_POST["cl_phone"]);
      $cl_email = mysql_escape_string($_POST["cl_email"]);
      $cluster_id = mysql_escape_string($_POST["cluster_id"]);
      mysql_query("BEGIN");
      if(FALSE == mysql_select_db("mysql")){
        header ("Location: show_error.php?error=".mysql_error($mysql));
        mysql_close($mysql);
        exit(1);
        }
      $query = "SELECT user FROM user WHERE user = '$cl_login' AND host = 'localhost'";
      $result = mysql_query($query);
      if(FALSE == $result){
        header ("Location: show_error.php?error=".mysql_error($mysql));
        mysql_query("ROLLBACK");
        mysql_close($mysql);
        exit(1);
        }
      $n = mysql_num_rows($result);
      if($n != 0){
        header ("Location: show_error.php?error=Користувач з лог╕ном ".$cl_login." вже заре╓стрований");
        exit(0);
        }
      if(FALSE == mysql_select_db($db)){
        header ("Location: show_error.php?error=".mysql_error($mysql));
        mysql_close($mysql);
        exit(1);
        }
      $query = "INSERT INTO userlevel(level_id, user)
          SELECT permlevel.id, '$cl_login'
          FROM permlevel
          WHERE permlevel.level = 'client'";
      if(FALSE == mysql_query($query)){
        header ("Location: show_error.php?error=".mysql_error($mysql));
        mysql_query("ROLLBACK");
        mysql_close($mysql);
        exit(1);
        }
      if($_POST['pw1'] == ""){
        $cl_passwd = genpasswd(10);
        }
      else{
        $cl_passwd = mysql_escape_string($_POST["pw1"]);
        }
      if($_POST['createmailbox'] == 'on'){
        $cmd = sprintf("ssh -l root -i %s %s useradd %s \"%s\" \"%s\"", 
	    $prefix."/.ssh/id_dsa",
            $mailhub, 
            escapeshellarg($_POST["newmailbox"]), 
            $cl_passwd,
            escapeshellarg($_POST["cl_description"]));
        exec($cmd);
        }
      // Put record in the table client
      $query = "INSERT INTO client(login, description, person, phone, email, activation_time)
          VALUES('$cl_login', 
          '$cl_description', 
          '$cl_person', 
          '$cl_phone', 
          '$cl_email',
					NOW())";
      if(FALSE == mysql_query($query)){
        header ("Location: show_error.php?error=".mysql_error($mysql));
        mysql_query("ROLLBACK");
        mysql_close($mysql);
        exit(1);
        }
      $client_id = mysql_insert_id();
      log_event($mysql, $client_id, "client", $client_id, "add", "New customer:\n$query");
			if($cluster_id != 0){
        $query = "INSERT INTO client_cluster(client_id, cluster_id)
            VALUES('$client_id', '$cluster_id')";
        if(FALSE == mysql_query($query)){
          header ("Location: show_error.php?error=".mysql_error($mysql));
          mysql_query("ROLLBACK");
          mysql_close($mysql);
          exit(1);
          }
      	log_event($mysql, $client_id, "client_cluster", mysql_insert_id(), "add", "Сustomer is placed to cluster id $cluster_id");
        }
      mysql_query("COMMIT");
      // Add mysql account for user
      $query = "grant select on `$db`.* to '$cl_login'@'localhost' identified by '$cl_passwd'";
      if(FALSE == mysql_query($query)){
        header ("Location: show_error.php?error=".mysql_error($mysql));
        mysql_close($mysql);
        exit(1);
        }
      $query = "grant insert  on `$db`.`log_event` to '$cl_login'@'localhost'";
      if(FALSE == mysql_query($query)){
        header ("Location: show_error.php?error=".mysql_error($mysql));
        mysql_close($mysql);
        exit(1);
        }
      $query = "grant insert  on `$db`.`order_report` to '$cl_login'@'localhost'";
      if(FALSE == mysql_query($query)){
        header ("Location: show_error.php?error=".mysql_error($mysql));
        mysql_close($mysql);
        exit(1);
        }
      $query = "grant insert  on `$db`.`voting` to '$cl_login'@'localhost'";
      if(FALSE == mysql_query($query)){
        header ("Location: show_error.php?error=".mysql_error($mysql));
        mysql_close($mysql);
        exit(1);
        }
      $query = "grant insert,delete  on `$db`.`maillist` to '$cl_login'@'localhost'";
      if(FALSE == mysql_query($query)){
        header ("Location: show_error.php?error=".mysql_error($mysql));
        mysql_close($mysql);
        exit(1);
        }
      $query = "grant insert  on `$db`.`tariff` to '$cl_login'@'localhost'";
      if(FALSE == mysql_query($query)){
        header ("Location: show_error.php?error=".mysql_error($mysql));
        mysql_close($mysql);
        exit(1);
        }
      $query = "grant insert,update  on `$db`.`service` to '$cl_login'@'localhost'";
      if(FALSE == mysql_query($query)){
        header ("Location: show_error.php?error=".mysql_error($mysql));
        mysql_close($mysql);
        exit(1);
        }
      $query = "grant insert,update  on `$db`.`drweb_avd_customer` to '$cl_login'@'localhost'";
      if(FALSE == mysql_query($query)){
        header ("Location: show_error.php?error=".mysql_error($mysql));
        mysql_close($mysql);
        exit(1);
        }
      mysql_close($mysql);
      if($_POST['sendemail'] == 'on'){
        $headers = "From: ".$support_name."<".$support_email.">\n";
        $headers .= "Content-Type: text/plain; charset=koi8-r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\n";
        mail("$_POST[email2send]", "New account for you", 
"  Уважаемый пользователь!
Для Вас была создана учетная запись на нашем сервере статистики.
Чтобы получить информацию о трафике, Вы можете зайти на сайт ".$start_url."

Логин $cl_login
Пароль $cl_passwd


  Dear customer!
New account at our statistic server has been created.
Using ".$start_url." you can see your traffic usage.
Login $cl_login
Password $cl_passwd

-- 
".$support_name.",
E-mail: ".$support_email."
Phone: ".$support_phone, $headers, "-f".$support_email);
      
      mail($noc_email, "New account for ".$cl_login, 
"  Уважаемый пользователь!
Для Вас была создана учетная запись на нашем сервере статистики.
Чтобы получить информацию о трафике, Вы можете зайти на сайт ".$start_url."

Логин $cl_login
Пароль $cl_passwd

  Dear customer!
New account at our statistic server has been created.
Using ".$start_url." you can see your traffic usage.
Login $cl_login
Password $cl_passwd

-- 
".$support_name.",
E-mail: ".$support_email."
Phone: ".$support_phone, $headers, "-f".$support_email);
      }
    if($_POST['2newwindow'] == 'on'){
      ?>
      <tr><td valign=top align=center>
      <table><caption>Карточка клиента</caption>
      <tr><th>Логин
          <td><?echo $_POST[cl_login];?>
      <tr><th>Пароль
          <td><?echo $cl_passwd;?>
      </table>
      </td></tr>
      <?
      }
    else{
      header("Location: show_ll.php?cluster_id=".$cluster_id);
			exit(0);
      }
    }
  }
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
