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
			&& $_SESSION['authdata']['permlevel'] != 'client'
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
	if($permlevel == "client"){
    $query = "select id from client where login = '$login'";
    $result = mysql_query($query);
    if($result == FALSE){
    	$msg = "Error: ".mysql_error()." while executing:\n".$query;
    	$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
    	header ("Location: show_error.php?error=".$msg);
			mysql_close($mysql);
    	exit(1);
      }
    $client_id = mysql_escape_string(mysql_result($result, 0, "id"));
    }
	else{
		$client_id = mysql_escape_string($_GET["client_id"]);
		}
  // If user has "client" privileges, check his validity
  if($permlevel == 'client'){
		if(!check_client_validity($client_id, $mysql)){
			header($start_url);
			}
    }
  $query = "SELECT client.id,
    client.description,
    client.manager_id,
    client.person,
    client.phone,
    client.email
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
  if($permlevel == "manager"){
    $readonlystatus = "readonly";
    if($login != mysql_result($result, 0, "client.manager_id")){
      header($start_url);
      }
    }
  if($permlevel == 'client'){
?>
  <tr>
    <td valign="top" align="right"><a href="logout.php">Вийти</a></td>
  </tr>
<?
    }
  // Report id not selected - show list of them
  if(!isset($_GET["report_id"])){
		$query = "SELECT
            order_report.id,
            order_report.report_type,
            order_report.starttimestamp,
            order_report.stoptimestamp
            FROM report
            LEFT JOIN order_report ON order_report.id = report.id
            WHERE order_report.client_id = '$client_id'";
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
?>
  <tr>
    <td>
      <table>
        <tr>
          <td colspan="4">Тут публ╕куються замовлен╕ Вами зв╕ти про траф╕к</td>
        </tr>
<?
    for($i = 0; $i < $n; $i++){
      $self_url = "<a href=\"detailed_report.php?cl_login=$login&amp;client_id=$client_id&amp;report_id=".mysql_result($result, $i, "order_report.id")."\">";
?>
        <tr>
          <td><?echo $self_url?><? echo mysql_result($result, $i, "order_report.id")?></a></td>
          <td><?echo $self_url?><? echo mysql_result($result, $i, "order_report.report_type")?></a></td>
          <td><?echo $self_url?><? echo mysql_result($result, $i, "order_report.starttimestamp")?></a></td>
          <td><?echo $self_url?><? echo mysql_result($result, $i, "order_report.stoptimestamp")?></a></td>
        </tr>
<?
      }
?>
      </table>
    </td>
  </tr>
<?	
		log_event($mysql, $client_id, "report, order_report", "", "view", "Get reports");
    }
  else{
    $report_id = mysql_escape_string($_GET["report_id"]);
    $query = "SELECT report.report_body 
        FROM client 
        LEFT JOIN order_report ON order_report.client_id = client.id
        LEFT JOIN report ON report.id = order_report.id 
        WHERE report.id = '$report_id' and client.id = '$client_id'";
    $result = mysql_query($query);
    if($result == FALSE){
    	$msg = "Error: ".mysql_error()." while executing:\n".$query;
    	$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
    	header ("Location: show_error.php?error=".$msg);
			mysql_close($mysql);
    	exit(1);
      }
    if(mysql_num_rows($result) != 0){
?>
  <tr>
    <td align="left"><pre><?echo "\n ".mysql_result($result, 0, "report_body")?></pre>
    </td>
  </tr>
<?
      log_event($mysql, $client_id, "report", $report_id, "view", "Get report id $report_id");
			}
    }
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
