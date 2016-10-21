<? // Here are options for web scripts
  $prefix="/home/netstore";
  $htdocs_dir="/home/netstore/htdocs";
  $bin_dir="/home/netstore/bin";
  $start_url="Location: https://polynom.nbi.com.ua/";
  // Mysql section
  $host="localhost";
  $db="netstore";
  $radius_db="radius";
  $T3="/var/db/netstore/T3";

  $_SERVER["PATH"] .= ":/usr/local/bin";
  $_SERVER["PATH"] .= ":/home/netstore/bin";
  $TMPDIR="/tmp";
  define("FPDF_FONTPATH","font/");
  // host where mailboxes are hosted
  $mailhub="mail.nbi.com.ua";
  // MX networks where we can send detailed reports
  $allowed_network[0] = "80.78.32.0";
  $allowed_netmask[0] = "255.255.224.0";
  $allowed_network[1] = "81.21.0.0";
  $allowed_netmask[1] = "255.255.240.0";
  $company_name="Company Name Inc.";
  $support_name="Support Team";
  $support_email="root@polynom.nbi.com.ua";
  $noc_email="root@polynom.nbi.com.ua";
  $support_phone="+380 44 201 02 03";
  setlocale(LC_TIME, "uk_UA.KOI8-U");
  setlocale(LC_COLLATE, "uk_UA.KOI8-U");
  header("Cache-Control: no-store, no-cache, must-revalidate");
?>
