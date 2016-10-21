<?
	
	// Document root
	$prefix = "/home/netstore/dev";
	$bin_dir = "/home/netstore/bin";
	$start_url = "Location: http://netstore.nbi.com.ua:8080/";
	// Mysql section

	$host = "localhost";
	$db = "netstore_dev";
	$radius_db = "radius_dev";
	$T3 = "/var/db/netstore/T3";

	$_SERVER["PATH"] .= ":/usr/local/bin";
	$TMPDIR = "/tmp";
	define("FPDF_FONTPATH","font/");

	// host where mailboxes are hosted
	$mailhub = "mail.nbi.com.ua";
	
	// MX networks where we can send detailed reports
	$allowed_network[0] = "80.78.32.0";
	$allowed_netmask[0] = "255.255.224.0";

 	// Intellcom
	$allowed_network[1] = "81.21.0.0";
	$allowed_netmask[1] = "255.255.240.0";
	// Skif
	$allowed_network[2] = "193.108.120.0";
	$allowed_netmask[2] = "255.255.254.0";
	$allowed_network[3] = "195.20.97.0";
	$allowed_netmask[3] = "255.255.254.0";
	// Ipri
	$allowed_network[4] = "194.44.146.0";
	$allowed_netmask[4] = "255.255.254.0";


	$company_name = "קבפ \"מג¶\"";
	
	$support_name = "NBI Support Team";
	$support_email = "support@nbi.com.ua";
	$noc_email = "noc@nbi.com.ua";
	$support_phone = "201 02 03";
	
	setlocale(LC_TIME, "uk_UA.KOI8-U");
	setlocale(LC_COLLATE, "uk_UA.KOI8-U");
	header("Cache-Control: no-store, no-cache, must-revalidate");
?>
