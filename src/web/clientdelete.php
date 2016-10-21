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
    session_timeout();
		}
  if(FALSE == mysql_select_db($db)){
    $msg = mysql_error();
		header ("Location: show_error.php?error=".$msg);
		mysql_close($mysql);
		exit(1);
		}
  $client_id = mysql_escape_string($_GET["client_id"]);
  $cluster_id = mysql_escape_string($_GET["cluster_id"]);
	mysql_query("BEGIN");
  // Get login from client_id
  $query = "SELECT login, vpn FROM client WHERE id = '$client_id'";
  $result = mysql_query($query);
  if($result == FALSE){
		$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		mysql_query("ROLLBACK");
    mysql_close($mysql);
    exit(1);
    }
  if(1 != mysql_num_rows($result)){
    $msg = "Кл╕╓нта з ╕дентиф╕катором $client_id в баз╕ не ╕сну╓";
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
		mysql_close($mysql);
    exit(1);
    }
  $cl_login = mysql_escape_string(mysql_result($result, 0, "login"));
  $vpn = mysql_escape_string(mysql_result($result, 0, "vpn"));
  delete_filters($mysql, $client_id, "no");
	delete_services($mysql, $client_id, "no");
	delete_contracts($mysql, $client_id, "no");
  
	$query = "DELETE FROM client_cluster WHERE client_id = '$client_id'";
  if(FALSE == mysql_query($query)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		mysql_query("ROLLBACK");
    mysql_close($mysql);
    exit(1);
    }
  $query = "DELETE FROM client_interface WHERE client_id = '$client_id'";
  if(FALSE == mysql_query($query)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		mysql_query("ROLLBACK");
    mysql_close($mysql);
    exit(1);
    }
  $query = "DELETE FROM client_network WHERE client_id = '$client_id'";
  if(FALSE == mysql_query($query)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		mysql_query("ROLLBACK");
    mysql_close($mysql);
    exit(1);
    }
	if($vpn == "n"){
		$query = "SELECT radreply_id FROM locked_client WHERE client_id = '$client_id'";
		$result = mysql_query($query);
		if(FALSE == $result){
		  $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
		  mysql_close($mysql);
		  exit(1);
		  }
		$n = mysql_num_rows($result);
		for($i = 0; $i < $n; $i++){
			$radreply_id = mysql_result($result, $i, "radreply_id");
			$q = "DELETE FROM `$radius_db`.radreply WHERE `$radius_db`.radreply.id = '$radreply_id'";
			if(FALSE == mysql_query($q)){
			  $msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".$msg);
				mysql_query("ROLLBACK");
			  mysql_close($mysql);
			  exit(1);
			  }
			}
		$query = "DELETE FROM locked_client WHERE client_id = '$client_id'";
		if(FALSE == mysql_query($query)){
		  $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
		  mysql_close($mysql);
		  exit(1);
		  }
		}
	else{ // Delete vpn client
		$query = "DELETE FROM `$radius_db`.client WHERE UserName = '$cl_login'";
		if(FALSE == mysql_query($query)){
		  $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
		  mysql_close($mysql);
		  exit(1);
		  }
		$query = "DELETE FROM `$radius_db`.radcheck WHERE UserName = '$cl_login'";
		if(FALSE == mysql_query($query)){
		  $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
		  mysql_close($mysql);
		  exit(1);
		  }
		$query = "DELETE FROM `$radius_db`.radreply WHERE UserName = '$cl_login'";
		if(FALSE == mysql_query($query)){
		  $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
		  mysql_close($mysql);
		  exit(1);
		  }
		$query = "DELETE FROM `$radius_db`.usergroup WHERE UserName = '$cl_login'";
		if(FALSE == mysql_query($query)){
		  $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK");
		  mysql_close($mysql);
		  exit(1);
		  }
		}
	
	// Delete record from the table userlevel
  $query = "DELETE FROM userlevel WHERE user = '$cl_login'";
  if(FALSE == mysql_query($query)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		mysql_query("ROLLBACK");
    mysql_close($mysql);
    exit(1);
    }
  // Delete  record from the table client
  $query = "DELETE FROM client WHERE id = '$client_id'";
  if(FALSE == mysql_query($query)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		mysql_query("ROLLBACK");
    mysql_close($mysql);
    exit(1);
    }
	log_event($mysql, $client_id, "client", $client_id, "delete", "Removed client id $client_id, login $cl_login");
  mysql_query("COMMIT");
  // Delete mysql account for user
  if(FALSE == mysql_select_db('mysql')){
    $msg = "Error: ".mysql_error($mysql)." while selecting database `mysql'\n";
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  $query = "DELETE FROM user WHERE user = '$cl_login'";
  if(FALSE == mysql_query($query)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  $query = "DELETE FROM db WHERE user = '$cl_login'";
  if(FALSE == mysql_query($query)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  $query = "DELETE FROM tables_priv WHERE user = '$cl_login'";
  if(FALSE == mysql_query($query)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
	log_event($mysql, $client_id, "mysql.user", $client_id, "delete", "Revoke premissions for login $cl_login");
  mysql_close($mysql);
  header("Location: show_ll.php?cluster_id=".$cluster_id);
	exit(0);
?>
