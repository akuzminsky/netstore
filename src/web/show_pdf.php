<?
	include("netstorecore.php");
	session_start();
	if(!@session_is_registered('authdata')){
		header($start_url);
		$msg = "���������� �� �������Ʀ�������";
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		exit(1);
		}
	if($_SESSION['authdata']['permlevel'] != 'admin'
			&& $_SESSION['authdata']['permlevel'] != 'topmanager'){
		$msg = "���������� �� ������������� ���������� ���� Ħ�";
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		exit(1);
		}
	$f = $_GET["f"];
 //Check file (don't skip it!)
	if(substr($f, 0, strlen("$TMPDIR/netstore")) != "$TMPDIR/netstore"
 			or strpos($f, "..") != FALSE){
		$msg = "���� ".$f." �� ���� ���� ���������";
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		exit(1);
		}
	if(!file_exists($f)){
		$msg = "���� ".$f." �� ���դ";
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		exit(1);
		}
 //Handle special IE request if needed
 //Output PDF
	header("Content-Type: application/pdf");
	header("Content-Length: ".filesize($f));
	readfile($f);
 //Remove file
	unlink($f);
	exit(0);
?> 

