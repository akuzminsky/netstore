<?
 $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
 readfile("$DOCUMENT_ROOT/head.html");
 include("$DOCUMENT_ROOT/netstorecore.php");
 session_start();
 if(!@session_is_registered('authdata')){
 	header($start_url);
	}
 session_destroy();
 header($start_url);
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
