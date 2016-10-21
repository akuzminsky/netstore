<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  //readfile("$DOCUMENT_ROOT/head.html");
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
  $login = $authdata['login'];
  $passwd = $authdata['passwd'];
  $permlevel = $authdata['permlevel'];
	
	$file = $TMPDIR ."/". basename($_GET["file"]);
	header("Content-disposition: attachment; filename=NetStore".date("Ym").".pdf");
	header("Content-Type: application/pdf");
	header("Content-Length: ".filesize($file));
	readfile($file);
	exit(0);
?>
