<?
	include("netstorecore.php");
	echo "<pre>\n";
	passthru($bin_dir."/utc-ix.sh");
	echo "</pre>\n";
?>
