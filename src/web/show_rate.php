<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  readfile("$DOCUMENT_ROOT/head.html");
  include("$DOCUMENT_ROOT/netstorecore.php");
  session_start();
  if(!@session_is_registered('authdata')){
    header($start_url);
    }
  if($_SESSION['authdata']['permlevel'] != 'admin' 
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
    header ("Location: show_error.php?error=".mysql_error());
    exit(1);
    }
  if(FALSE == mysql_select_db($db)){
    $msg = "Error: ".mysql_error()." while connecting to database ".$db;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  $REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
  if($REQUEST_METHOD == "POST"){
    $year = mysql_escape_string($_POST["year"]);
    $month = mysql_escape_string($_POST["month"]);
    $num_days = mysql_escape_string($_POST["num_days"]);
    if(!empty($_POST["save"])){
      // Here all updates are saved
      mysql_query("BEGIN");
      for($i = 1; $i <= $num_days; $i++){
        $rate = mysql_escape_string($_POST["rate".$i]);
        $query = "REPLACE INTO `rate`(`date`, `rate`) VALUES('$year-$month-$i', $rate)";
        if(FALSE == mysql_query($query)){
          $msg = "Error: ".mysql_error()." while executing:\n".$query;
					$msg = str_replace("\n", "<br>", $msg);
					$msg = urlencode($msg);
					header ("Location: show_error.php?error=".$msg);
          mysql_query("ROLLBACK");
          mysql_close($mysql);
          exit(1);
          }
        }
      mysql_query("COMMIT");
      }
    }
  if($REQUEST_METHOD == "GET"){
    $year = mysql_escape_string($_GET["year"]);
    $month = mysql_escape_string($_GET["month"]);
    }
?>
  <tr>
    <td valign="top" width="20%"><? include("left_bk.php");?></td>
    <td width="80%">
      <table>
        <tr>
          <td>
            <table>
              <tr>
<?
  $today = getdate();
  for($iyear = $today["year"] + 1; $iyear >= $today["year"] - 5; $iyear--){
    if($year == $iyear){
?>
                <td><b><? echo $iyear?></b></td>
<?
      }
    else{
?>
                <td><a href="show_rate.php?year=<? echo $iyear; ?>&amp;month=<? echo $month; ?>"><? echo $iyear?></a></td>
<?
      }
    }
?>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td>
            <table cellspacing="1" cellpadding="2" bgcolor="silver">
<?
  $imonth = 1;
  for($i = 1; $i <= 3; $i++){
?>
              <tr bgcolor="white">
<?
    for($j = 1; $j <= 4; $j++){
      $ts = mktime(0, 0, 0, $imonth, 1, $year);
      $month_name = strftime("%B", $ts);
      if($imonth == $month){
?>
                <td><b><? echo $month_name; ?></b></td>
<?
        }
      else{
?>
                <td><a href="show_rate.php?year=<? echo $year; ?>&amp;month=<? echo $imonth; ?>"><? echo $month_name; ?></a></td>
<?
        }
      $imonth++;
      }
?>
              </tr>
<?
    }
          
?>
            </table>
          </td>
        </tr>
        <tr>
          <td>
            <form method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
            <table border="0">
              <tr>
<?
    $query = "SELECT 
        `id`,
        `date`,
        `rate`,
        DAYOFMONTH(`date`) as mday
        FROM `rate`
        WHERE MONTH(`date`) = $month
        AND YEAR(`date`) = $year
        ORDER BY `date`";
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
    $ires = 0;
    $t = getdate(mktime(0, 0, 0, $month + 1, 0, $year));
    $lastday = $t["mday"];
    $iday = 1;
    for($i = 1; $i <= 4; $i++){
?>
                <td valign="top">
                  <table cellspacing="1" cellpadding="2" bgcolor="silver">
<?
      for($j = 1; $j <= 10; $j++){
        if($iday > $lastday){
          break;
          }
        $rate = 0;
        if($ires < $n){
          if(mysql_result($result, $ires, "mday") == $iday){
            $rate = mysql_result($result, $ires, "rate");
            $ires++;
            }
          }
?>
                  <tr bgcolor="white">
                    <td><? echo $iday; ?></td>
                    <td><input type="text" name="rate<?echo $iday;?>" value="<? echo $rate?>"></td>
                  </tr>
<?
        $iday++;
        }
?>
                  </table>
                </td>
<?
      }
?>
              </tr>
              <tr>
                <td align="right" colspan="3"><input type="submit" name="save" value="Зберегти"></td>
              </tr>
            </table>
            <input type="hidden" name="year" value="<? echo $year?>">
            <input type="hidden" name="month" value="<? echo $month?>">
            <input type="hidden" name="num_days" value="<? echo $iday - 1;?>">
            </form>
          </td>
        </tr>
      </table>
    </td>
  </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
