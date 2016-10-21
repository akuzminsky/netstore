<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  readfile("$DOCUMENT_ROOT/head.html");
  session_start();
  if(!@session_is_registered('authdata') || $_SESSION['authdata']['permlevel'] != 'admin'){
    header($start_url);
    }
  if($_SESSION['authdata']['permlevel'] != 'admin'
      && $_SESSION['authdata']['permlevel'] != 'support'){
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
  mysql_query("BEGIN");
  $resp_manager = mysql_escape_string($_POST['resp_manager']);
  $cl_description = mysql_escape_string($_POST['cl_description']);
  $cl_full_name = mysql_escape_string($_POST['cl_full_name']);
  $cl_person = mysql_escape_string($_POST['cl_person']);
  $cl_phone = mysql_escape_string($_POST['cl_phone']);
  $cl_email = mysql_escape_string($_POST['cl_email']);
  $cl_port = mysql_escape_string($_POST['cl_port']);
  $cl_notes = mysql_escape_string($_POST['cl_notes']);
  $client_id = mysql_escape_string($_POST['client_id']);
  $vpn = mysql_escape_string($_POST['vpn']);
  $vpn = ($vpn == "on") ? "y" : "n";
  $save_flows = mysql_escape_string($_POST['save_flows']);
  $save_flows = ($save_flows == "on") ? "yes" : "no";
  $unlimited = mysql_escape_string($_POST['unlimited']);
  $unlimited = ($unlimited == "on") ? "yes" : "no";
  $has_equipment = mysql_escape_string($_POST['has_equipment']);
  $has_equipment = ($has_equipment == "on") ? "yes" : "no";
  $connection_speed = mysql_escape_string($_POST['connection_speed']);
  $gone = mysql_escape_string($_POST['gone']);
  // Retrieve previous inactivation date
  $q = "SELECT `inactivation_time` 
      FROM `client`
      WHERE id = '$client_id'";
  $r = mysql_query($q);
  if($r == FALSE){
    $msg = "Error: ".mysql_error()." while executing:\n".$q;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
    mysql_close($mysql);
    exit(1);
    }
  if(mysql_num_rows($r) == 0){
    $msg = "Error: No such user id = $client_id\n";
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
    mysql_close($mysql);
    exit(1);
    }
  $inactivation_time_prev = mysql_result($r, 0, "inactivation_time");
  //echo "inactivation_time_prev = ".$inactivation_time_prev.$q; exit(0);
  // Set "inactivation_time" if it was NULL
  if($inactivation_time_prev == ""){
    $q = "SELECT
      IFNULL(MAX(expire_time), NOW()) AS expire
      FROM contract
      WHERE client_id = '$client_id'
      AND expire_time <> 0";
    $r = mysql_query($q);
    if($r == FALSE){
      $msg = "Error: ".mysql_error()." while executing:\n".$q;
      $msg = str_replace("\n", "<br>", $msg);
      $msg = urlencode($msg);
      header ("Location: show_error.php?error=".$msg);
      mysql_query("ROLLBACK");
      mysql_close($mysql);
      exit(1);
      }
    $inactivation_time_prev = mysql_result($r, 0, "expire");
    }
  $inactivation_time = ($gone == "on") ? "'".$inactivation_time_prev."'" : "NULL";
  if($inactivation_time != "NULL"){
    // Check if it blaocked.
    // if it is, aks to unblock!
    // Gone User can't be blocked.
    $q = "SELECT blocked FROM `client` WHERE `id` = '$client_id'";
    $r = mysql_query($q);
    if($r == FALSE){
      $msg = "Error: ".mysql_error()." while executing:\n".$q;
      $msg = str_replace("\n", "<br>", $msg);
      $msg = urlencode($msg);
      header ("Location: show_error.php?error=".$msg);
      mysql_query("ROLLBACK");
      mysql_close($mysql);
      exit(1);
      }
    if(mysql_result($r, 0, "blocked") == "y"){
      mysql_query("ROLLBACK");
      $msg = "Error: Кл╕╓нт '$cl_description' тимчасово закритий. Для того, щоб встановити ознаку \"Вибувший\", необх╕дно спочатку його в╕дкрити.";
      $msg = str_replace("\n", "<br>", $msg);
      $msg = urlencode($msg);
      header ("Location: show_error.php?error=".$msg);
      exit(1);
      }  
    $q = "DELETE FROM maillist WHERE client_id ='$client_id'";
    $r = mysql_query($q);
    if($r == FALSE){
      $msg = "Error: ".mysql_error()." while executing:\n".$q;
      $msg = str_replace("\n", "<br>", $msg);
      $msg = urlencode($msg);
      header ("Location: show_error.php?error=".$msg);
      mysql_query("ROLLBACK");
      mysql_close($mysql);
      exit(1);
      }
    }
  $query = "UPDATE client set
      manager_id = '$resp_manager',
      description = '$cl_description',
      full_name = '$cl_full_name',
      person = '$cl_person',
      phone = '$cl_phone',
      email = '$cl_email',
      port = '$cl_port',
      vpn = '$vpn',
      save_flows = '$save_flows',
      unlimited = '$unlimited',
      has_equipment = '$has_equipment',
      connection_speed = '$connection_speed',
      inactivation_time = $inactivation_time,
      notes = '$cl_notes'
      WHERE id = '$client_id'";
  if(FALSE == mysql_query($query)){
    $msg = "Error: ".mysql_error()." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
    mysql_close($mysql);
    exit(1);
    }
  log_event($mysql, $client_id, "client", $client_id, "update", "Updated client info. Query:\n$query");
  $cl_bc = mysql_escape_string($_POST['cl_bc']);
  if($_POST['withbc'] == 'on' && $cl_bc != 0){
    // Check if the customer belongs to this bc.
    $query = "SELECT * FROM `client_cluster` WHERE `cluster_id` = '$cl_bc' AND `client_id` = '$client_id'";
    $result = mysql_query($query);
    if(FALSE == $result){
      $msg = "Error: ".mysql_error()." while executing:\n".$query;
      $msg = str_replace("\n", "<br>", $msg);
      $msg = urlencode($msg);
      header ("Location: show_error.php?error=".$msg);
      mysql_close($mysql);
      exit(1);
      }
    
    $client_belongs_to_this_bc = (mysql_num_rows($result) > 0) ? true : false;
    
    $query = "SELECT * FROM `client_cluster` WHERE `client_id` = '$client_id'";
    $result = mysql_query($query);
    if(FALSE == $result){
      $msg = "Error: ".mysql_error()." while executing:\n".$query;
      $msg = str_replace("\n", "<br>", $msg);
      $msg = urlencode($msg);
      header ("Location: show_error.php?error=".$msg);
      mysql_close($mysql);
      exit(1);
      }
    
    $client_belongs_to_bc = (mysql_num_rows($result) > 0) ? true : false;
    
    if($client_belongs_to_bc && !$client_belongs_to_this_bc){
      $query = "UPDATE `client_cluster` SET `cluster_id` = '$cl_bc' WHERE `client_id` = '$client_id'";
      $result = mysql_query($query);
      if($result == FALSE){
        $msg = "Error: ".mysql_error()." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      log_event($mysql, $client_id, "cluster", $cl_bc, "update", "The customer $client_id has moved to the cluster $cl_bc \nQuery:\n$query");
      }
    
    if(!$client_belongs_to_bc){
      // The customer doesn't belong to BC.
      $query = "INSERT INTO
          client_cluster(client_id, cluster_id) 
          VALUES($client_id, $cl_bc)";
      if(FALSE == mysql_query($query)){
        $msg = "Error: ".mysql_error()." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      log_event($mysql, $client_id, "client_cluster", mysql_insert_id(), "add", "Put client id $client_id to cluster id ".$cl_bc);
      }
    if($client_belongs_to_bc && $client_belongs_to_this_bc){
      // If the customer belongs to BC, we can safely update BC info
      // Update BC related info
      $network = mysql_escape_string($_POST['network']);
      $gateway = mysql_escape_string($_POST['gateway']);
      $switch = mysql_escape_string($_POST['switch']);
      $equipment = mysql_escape_string($_POST['equipment']);
      $query = "UPDATE cluster SET
          network = '$network',
          gateway = '$gateway',
          switch = '$switch',
          equipment = '$equipment'
          WHERE id = $cl_bc";
      $result = mysql_query($query);
      if($result == FALSE){
        $msg = "Error: ".mysql_error()." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      log_event($mysql, $client_id, "cluster", $cl_bc, "update", "Update cluster info:\n$query");
      }
    }
    else{ // The customer doesn't belong to any BC
      $client_id = mysql_escape_string($_POST['client_id']);
      $query = "delete from client_cluster where client_id = $client_id";
      if(FALSE == mysql_query($query)){
        $msg = "Error: ".mysql_error()." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      log_event($mysql, $client_id, "client_cluster", "", "delete", "Remove client id $client_id from cluster");
      }
  mysql_query("COMMIT");
  mysql_close($mysql);
  header("Location: show_client.php?client_id=$client_id");
  exit(0);
?>
