<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  $REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
  readfile("$DOCUMENT_ROOT/head.html");
  include("$DOCUMENT_ROOT/netstorecore.php");
  session_start();
  if(!@session_is_registered('authdata')){
    header($start_url);
    }
  if($_SESSION['authdata']['permlevel'] != 'admin'
      && $_SESSION['authdata']['permlevel'] != 'support'){
    header($start_url);
    exit(0);
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
    $msg = "Error: ".mysql_error()." while connecting to database ".$db;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  if($REQUEST_METHOD == "POST"){
    //echo "<pre>\n";
    //print_r($_POST);
    //print_r(array_keys($_POST["cl_"]));
     //echo "</pre>\n";
    $pbwidth = 300;
?>
  <tr>
    <td align="left" valign="top" width="20%"><?include("left_bk.php");?></td>
    <td width="80%">
      <table>
      <caption>Пов╕домлення в╕дправля╓ться. Будь ласка, зачекайте...</caption>
      <form name="pbform">
        <tr>
          <td width="<? echo $pbwidth; ?>"><img name="pb" src="img/gdot.png" height="20" width="<? echo $pbwidth; ?>"></td>
          <td><input type="text" name="pbtext" value="0" border="0" readonly></td>
        </tr>
      </form>
      </table>
    </td>
  </tr>
            
<?
    readfile("$DOCUMENT_ROOT/bottom.html");
    ob_end_flush();
    $num_rcpt = 0;
    foreach(array_keys($_POST["cl_"]) as $cluster_id){
       foreach(array_values($_POST["cl_"]["$cluster_id"]) as $client_id){
        $num_rcpt++;
        }
      }
    $step = $pbwidth / $num_rcpt;
    $width = 0;
    $message = $_POST["message"];
    $subject = $_POST["subject"];
    $headers = "From: ".$support_name." <".$support_email.">\r\n";
    $headers .= "Content-Type: text/plain;\n charset=\"koi8-u\"\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    $headers .= "Content-Disposition: inline\r\n";
    $headers .= "Organization: $company_name\r\n";
    $headers .= "User-Agent: NetStore mailer";
    $additional_parameters = "-f".$support_email;
    if($_POST["copy2support"] == "on"){
      mail($support_email, $subject, $message, $headers, $additional_parameters);
      }
    foreach(array_keys($_POST["cl_"]) as $cluster_id){
       foreach(array_values($_POST["cl_"]["$cluster_id"]) as $email){
        mail($email, $subject, $message, $headers, $additional_parameters);
        $width += $step;
        echo "<script language=\"javascript\">\n";
        echo "window.document.images('pb').width = ". intval($width) . ";\n";
        echo "window.document.forms['pbform'].elements['pbtext'].value = '". intval($width * 100 / $pbwidth) . " %';\n";
        echo "</script>\n";
        flush();
        }
      }
    echo "<script language=\"javascript\">window.document.forms['pbform'].elements['pbtext'].value = 'Завершено';</script>\n";
    //echo "<pre>";
    //print_r($bills);
    //echo "</pre>";
    exit(0);
    }
  if($REQUEST_METHOD == "GET"){
?>
  <tr>
    <td valign="top" width="20%"><? include("left_bk.php");?></td>
    <td width="80%">
      <form name="clients_form" method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
      <table cellspacing="1" cellpadding="2" bgcolor="silver">
      <caption>Рец╕п╕╓нти пов╕домлення</caption>
<?
  $query = "SELECT 
      client.id,
      client.description,
      client.email,
      IFNULL(cluster.id, 0) AS cluster_id,
      IF(cluster.description IS NULL, 'Вид╕лена л╕н╕я', cluster.description) AS cluster_description
      FROM client
      LEFT JOIN client_cluster ON client_cluster.client_id = client.id
      LEFT JOIN cluster ON cluster.id = client_cluster.cluster_id
      WHERE (client.inactivation_time IS NULL OR client.inactivation_time > NOW()) AND client.email <> ''
      ORDER BY cluster_description, client.description";
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
  $cluster_description_prev = "";
  for($i = 0; $i < $n; $i++){
    $client_id = mysql_result($result, $i, "client.id");
    $client_description = mysql_result($result, $i, "client.description");
    $cluster_id = mysql_result($result, $i, "cluster_id");
    $cluster_description = mysql_result($result, $i, "cluster_description");
    $email = mysql_result($result, $i, "client.email");
    if($cluster_description != $cluster_description_prev){
      $cluster_description_prev = $cluster_description;
?>
        <tr bgcolor="white">
          <td><input type="checkbox" name="bc_<? echo $cluster_id?>" onClick=" return setCheckboxes('clients_form', <? echo $cluster_id; ?>, <? echo $client_id; ?>);" checked></td>
          <th colspan="3" align="left"><? echo $cluster_description; ?></th>
        </tr>
<?
      }
?>
        <tr bgcolor="white">
          <td>&nbsp;</td>
          <td>
            <input type="checkbox" name="cl_[<? echo $cluster_id;?>][]" value="<? echo $email; ?>" checked>
          </td>
          <td><a href="show_client.php?client_id=<? echo $client_id; ?>"><? echo $client_description; ?></a></td>
          <td><a href="mailto:<? echo $email; ?>"><? echo $email; ?></a></td>
        </tr>
<?
    }
?>    
        <tr bgcolor="white">
          <td><input type="checkbox" name="copy2support" value="on"></td>
          <td colspan="3">В╕д╕слати коп╕ю пов╕домлення на <? echo $support_email; ?></td>
        </tr>
        <tr bgcolor="white">
          <td>Тема:</td>
          <td colspan="3" width="100%"><input type="text" name="subject" value="[NetStore Info]: " size="80"></td>
        </tr>
        <tr bgcolor="white">
          <td>&nbsp;</td>
          <td colspan="3"><textarea name="message" cols="90" rows="20"><? echo "--\n$support_name\n$support_email"?></textarea>
          </td>
        </tr>
        <tr bgcolor="white">
          <td colspan="4"><input type="submit" name="save" value="В╕дправити пов╕домлення"></td>
        </tr>
      </table>
    </td>
  </tr>
<?
    readfile("$DOCUMENT_ROOT/bottom.html");
    }
?>
