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
    && $_SESSION['authdata']['permlevel'] != 'support' 
    && $_SESSION['authdata']['permlevel'] != 'client'
    && $_SESSION['authdata']['permlevel'] != 'topmanager'){
    header($start_url);
    }
  $authdata = $_SESSION['authdata'];
  //include("top.php");
  $login = $authdata['login'];
  $passwd = $authdata['passwd'];
  $permlevel = $authdata['permlevel'];
  $mysql = @mysql_connect($host, $login, $passwd); mysql_set_charset("koi8u");
  if($mysql == FALSE){
    echo "Cannot connect to mysql server";
    exit(1);
    }
  if(FALSE == mysql_select_db($db)){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
  if($REQUEST_METHOD == "GET"){
    $client_id = mysql_escape_string($_GET["client_id"]);
    $dbegin = mysql_escape_string($_GET["dbegin"]);
    $dend = mysql_escape_string($_GET["dend"]);
    }

	if($dbegin == "" || $dend == ""){
		$dbegin = mktime(0, 0, 0, date("m"), 1, date("Y"));
		$dend = mktime();
		}

 // If user has "client" privileges, check his validity
  if($_SESSION['authdata']['permlevel'] == 'client'){
    if(!check_client_validity($client_id, $mysql)){
      header($start_url);
      }
    }
	// Generatin graphs
	$graph_prefix = "rrd_";
	$indiagram = "indiagram".session_id().".png";
	$outdiagram = "outdiagram".session_id().".png";
	@unlink("/tmp/".$graph_prefix.$indiagram);
	@unlink("/tmp/".$graph_prefix.$outdiagram);
	$cmd = "/usr/local/bin/rrdtool graph /tmp/".escapeshellarg($graph_prefix.$indiagram)." -s ".escapeshellarg($dbegin)." -e ".escapeshellarg($dend)." -h 600 -w 800 \
			--title \"Incoming Traffic\" \
			DEF:ifInOctets=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:ifInOctets:AVERAGE \
			DEF:SmtpIn=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:SmtpIn:AVERAGE \
			DEF:Pop3In=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:Pop3In:AVERAGE \
			DEF:WebIn=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:WebIn:AVERAGE \
			DEF:DnsIn=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:DnsIn:AVERAGE \
			DEF:MsnIn=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:MsnIn:AVERAGE \
			CDEF:Pop3=SmtpIn,Pop3In,+ \
			CDEF:Web=Pop3,WebIn,+ \
			CDEF:Dns=Web,DnsIn,+ \
			CDEF:Msn=Dns,MsnIn,+ \
			AREA:ifInOctets#28539B:Other \
			AREA:Msn#19D5D2:\"Microsoft Network\" \
			AREA:Dns#9CD528:DNS \
			AREA:Web#E8C63C:Web \
			AREA:Pop3#636363:POP3 \
			AREA:SmtpIn#FD7878:SMTP";
	exec($cmd);
	$cmd = "/usr/local/bin/rrdtool graph /tmp/".escapeshellarg($graph_prefix.$outdiagram)." -s ".escapeshellarg($dbegin)." -e ".escapeshellarg($dend)." -h 600 -w 800 \
			--title \"Outgoing Traffic\" \
			DEF:ifOutOctets=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:ifOutOctets:AVERAGE \
			DEF:SmtpOut=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:SmtpOut:AVERAGE \
			DEF:Pop3Out=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:Pop3Out:AVERAGE \
			DEF:WebOut=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:WebOut:AVERAGE \
			DEF:DnsOut=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:DnsOut:AVERAGE \
			DEF:MsnOut=/var/db/rrd/traffic".escapeshellarg($client_id).".rrd:MsnOut:AVERAGE \
			CDEF:Pop3=SmtpOut,Pop3Out,+ \
			CDEF:Web=Pop3,WebOut,+ \
			CDEF:Dns=Web,DnsOut,+ \
			CDEF:Msn=Dns,MsnOut,+ \
			AREA:ifOutOctets#28539B:Other \
			AREA:Msn#19D5D2:\"Microsoft Network\" \
			AREA:Dns#9CD528:DNS \
			AREA:Web#E8C63C:Web \
			AREA:Pop3#636363:POP3 \
			AREA:SmtpOut#FD7878:SMTP";
	exec($cmd);
	if(file_exists("/tmp/".$graph_prefix.$indiagram)){
?>

	<tr bgcolor="white">
    <td>
      <table border="0" width="100%" cellspacing="1" cellpadding="2" bgcolor="silver">
        <tr bgcolor="#D3D3D3">
          <th>Використання полоси пропускання</th>
        </tr>
        <tr bgcolor="#F5F5F5">
          <th>За пер╕од з <?echo strftime("%e %B %Y %X (%Z)",$dbegin)?> по <?echo strftime("%e %B %Y %X (%Z)",$dend - 1)?> </th>
        </tr>
        <tr bgcolor="#F5F5F5">
          <th><img src="<? echo $graph_prefix.$indiagram; ?>"></th>
        </tr>
        <tr bgcolor="#F5F5F5">
          <th><img src="<? echo $graph_prefix.$outdiagram; ?>"></th>
        </tr>
      </table>
    </td>
  </tr>
<?
		}
	readfile("$DOCUMENT_ROOT/bottom.html");
?>
