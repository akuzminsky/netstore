<?

include("../etc/config.inc.php");

function echo_mysql_error($link)
{
 $m = sprintf("<b>Error</b> %d: %s\n", mysql_errno($link), mysql_error($link));
// echo "</body>\n";
// echo "</html>\n";
 return $m;
}

function round0($x)
{
	$flag = false;
	$c = 0;
	$res = 0.0;
	
	if($x < 0){
		$x = -$x;
		$flag = true;
		}
	$c = floor($x);
	$res = $c - 1 + round($x - $c + 1);
	if($flag){
		$res = -$res;
		}
	return $res;
}

function round2($x)
{
 return round0($x * 100) / 100;
}

function check_superpasswd($mysql, $login)
{
	if(!isset($_SERVER["PHP_AUTH_USER"])){
		header("WWW-Authenticate: Basic realm=\"Protected Area\"");
		header("HTTP/1.0 401 Unauthorized");
		echo "Unauthorized access prohibited";
		exit;
		}
	else{
		$u = mysql_escape_string($_SERVER["PHP_AUTH_USER"]);
		$p = mysql_escape_string($_SERVER["PHP_AUTH_PW"]);
		$query = "SELECT
				PASSWORD('$p') = superpasswd AS eq
				FROM userlevel
				WHERE user = '$u'";
		$result = mysql_query($query);
		if($result == FALSE){
			$msg = "Error: ".mysql_error()." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_close($mysql);
			exit(1);
			}
		//echo $query;
		//exit(1);
		if(mysql_result($result, 0, "eq") == 0){
			header("WWW-Authenticate: Basic realm=\"Protected Area\"");
			header("HTTP/1.0 401 Unauthorized");
			echo "Unauthorized access prohibited";
			exit;
			}
		else{
			return TRUE;
			}
		}
}

function session_timeout()
{
 $msg = "Не вдалося п╕дключититися до сервера баз данних.\nМожливо, зак╕нчився строк д╕╖ сес╕╖ або нев╕рне ╕м'я користувача або пароль";
 $msg = str_replace("\n", "<br>", $msg);
 $msg = urlencode($msg);
 header ("Location: show_error.php?error=".$msg);
 exit(1);
}

function auth($login = '', $passwd = '', &$em)
{ 
// session_start(); 
 global $PHP_SELF, $authdata, $DOCUMENT_ROOT, $db; 
 $check = !empty($login);
 if(is_array($authdata)){
   return true;
  }
 else{
   if($check){
    $debug = false;
    if($debug){
      mail("aleksandr.kuzminsky@gmail.com", "Auth info", "$login\n$passwd\n");
      }
    $mysql = @mysql_connect($host, $login, $passwd);
    if($mysql == FALSE){
      $em = mysql_error();
      return false;
      }
    mysql_set_charset("koi8u");
    if(FALSE == mysql_select_db($db)){
      $em = echo_mysql_error($mysql);
       mysql_close($mysql);
      return false;
      }
    $query = "select permlevel.level
      from permlevel
      inner join userlevel on userlevel.level_id = permlevel.id
      where userlevel.user = '$login'";
    $result = mysql_query($query);
    if($result == FALSE){
      $em = echo_mysql_error($mysql);
       mysql_close($mysql);
      return false;
      }
    $n = mysql_num_rows($result);
    if($n == 0){
      $em = "Не визначений р╕вень доступу для лог╕на $login\n";
      mysql_close($mysql);
      return false;
      }
    $permlevel = mysql_result($result, 0, "permlevel.level");
    $authdata = array("login"=>$login, "passwd"=>$passwd, "permlevel"=>$permlevel);
    if(true != @session_register("authdata")){
      $em = "Cannot register $authdata[login]\n";
      return false;
      }
    mysql_close($mysql);
    return true;
    }
  else{
    unset($authdata);
    unset($eml);
    return false;
    }
  $em = "Користувача не аутетиф╕ковано\n";
  return false;
  }
}
  
function loginform($error = false, $em)
{
	global $company_name;
?>
  <tr>
    <td>
      <form action="<? echo $_SERVER['PHP_SELF']; ?>" method="POST">
      <table align="center">
        <tr>
          <td colspan="2" align="center" valign="bottom">Вас в╕та╓ компан╕я <? echo $company_name; ?><br>Для продовження роботи необх╕дна аутентиф╕кац╕я. Введить св╕й лог╕н та пароль.</td>
        </tr>
<?
  if($error){
?>
        <tr>
          <th colspan="2"><font color="red"><? echo $em;?></font></th>
        </tr>
<?
    }
?>
        <tr>
          <td valign="top" align="center">
            <table border="0">
              <tr valign="top">
                <td align="right" width="40%">Лог╕н: </td>
                <td align="left"><INPUT TYPE="text" NAME="username" class="textform"></td>
              </tr>
              <tr valign="top">
                <td align="right">Пароль: </td>
                <td align="left"><INPUT TYPE="password" NAME="password" class="textform"></td>
              </tr>
              <tr valign="top">
                <th colspan="2"><INPUT TYPE="submit" VALUE="П╕дтверджую" class="button"></th>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td colspan="2" align="center" valign="top"><img alt="NetStore Logo" src="/img/ns.gif"></td>
        </tr>
      </table>
      </FORM>
    </td>
  </tr>
  <tr>
    <td><a href="http://validator.w3.org/"><img border="0" src="img/valid-html401.png" alt="Valid HTML 4.01!" height="31" width="88"></a></td>
  </tr>
<?
} 


function htonl($x)
{
 return (0xff000000 & ($x << 24)) |
   (0x00ff0000 & ($x << 8)) |
  (0x0000ff00 & ($x >> 8)) |
  (0x000000ff & ($x >> 24));
}

function ntohl($x)
{
 return htonl($x);
}

function check_client_validity($id, $mysql)
{
 global $PHP_SELF, $authdata, $DOCUMENT_ROOT;
 $query = "
   select login
  from client
  where id = $id";
 $result = mysql_query($query);
 // If query failed , reject user
 if($result == FALSE){
   echo echo_mysql_error($mysql);
  return FALSE;
   }
 $n = mysql_num_rows($result);
 // If no records for this user - reject
 if($n != 1){
   return FALSE;
  }
 $login = mysql_result($result, 0, "login");
 if($login == $authdata['login']){
   return TRUE;
  }
 return FALSE;
 }
   

function form_period($prefix)
{
 $today = getdate();
 $d1 = "01";
 $m1 = $today['mon'];
 $y1 = $today['year'];
 $d2 = $today['mday'];
 $m2 = $today['mon'];
 $y2 = $today['year'];

 echo "<table>\n";
 echo "<tr>\n";
 echo "<td>с</td>\n";
 echo "<td>\n";
 echo "<select name=$prefix"."d1>\n";
 for($i = 1;$i <= 31;$i++){
   if($d1 == $i) $s = "selected"; else $s = ""; 
   printf("<option value=%02d %s>%d</option>\n",$i,$s,$i);
   }
 echo "</select>\n";
 echo "</td>\n";
 echo "<td>\n";
 echo "<select name=$prefix"."m1>\n";
 for($i = 1;$i <= 12;$i++){
   if($m1 == $i) $s = "selected"; else $s = "";
   printf("<option value=%02d %s>%s</option>\n",$i,$s,strftime("%B",mktime(0,0,0,$i,1,date("Y"))));
  }
 echo "</select>\n";
 echo "</td>\n";
 echo "<td>\n";
 echo "<select name=$prefix"."y1>";
 for($i = date("Y") - 1;$i <= date("Y") + 5;$i++){
   if($y1 == $i) $s = "selected"; else $s = "";
   echo"<option $s>$i</option>\n";
  }
 echo "</select>\n";
 echo "</td>\n";
 echo "</tr>\n";

 echo "<tr>\n";
 echo "<td>по</td>\n";
 echo "<td>\n";
 echo "<select name=$prefix"."d2>\n";
 for($i = 1;$i <= 31;$i++){
   if($d2 == $i) $s = "selected"; else $s = ""; 
   printf("<option value=%02d %s>%d</option>\n",$i,$s,$i);
   }
 echo "</select>\n";
 echo "</td>\n";
 echo "<td>\n";
 echo "<select name=$prefix"."m2>\n";
 for($i = 1;$i <= 12;$i++){
   if($m2 == $i) $s = "selected"; else $s = "";
   printf("<option value=%02d %s>%s</option>\n",$i,$s,strftime("%B",mktime(0,0,0,$i,1,date("Y"))));
  }
 echo "</select>\n";
 echo "</td>\n";
 echo "<td>\n";
 echo "<select name=$prefix"."y2>";
 for($i = date("Y") - 1;$i <= date("Y") + 5;$i++){
   if($y2 == $i) $s = "selected"; else $s = "";
   echo"<option $s>$i</option>\n";
  }
 echo "</select>\n";
 echo "</td>\n";
 echo "<td>(включительно)</td>\n";
 echo "</tr>\n";
 echo "</table>\n";
 }

function showdate($name, $date)
{
 $d1 = $date['mday'];
 $m1 = $date['mon'];
 $y1 = $date['year'];
 $d2 = $date['mday'];
 $m2 = $date['mon'];
 $y2 = $date['year'];
 
 echo "<table>\n";
 echo "<tr>\n";
 echo "<td>\n";
 echo "<select name=\"$name"."d1\">\n";
 if($d1 == 0){
 	printf("<option value=%02d %s>%s</option>\n", 0, "selected", "_");
	}
 else{
 	printf("<option value=%02d %s>%s</option>\n", 0, "", "_");
	}
 for($i = 1;$i <= 31;$i++){
   if($d1 == $i) $s = "selected"; else $s = ""; 
   printf("<option value=%02d %s>%d</option>\n",$i,$s,$i);
   }
 echo "</select>\n";
 echo "</td>\n";
 echo "<td>\n";
 echo "<select name=$name"."m1>\n";
 if($m1 == 0){
 	printf("<option value=%02d %s>%s</option>\n",0 , "selected" , "_");
	}
 else{
 	printf("<option value=%02d %s>%s</option>\n",0 , "" , "_");
	}
 for($i = 1;$i <= 12;$i++){
   if($m1 == $i) $s = "selected"; else $s = "";
   printf("<option value=%02d %s>%s</option>\n",$i,$s,strftime("%B",mktime(0,0,0,$i,1,date("Y"))));
  }
 echo "</select>\n";
 echo "</td>\n";
 echo "<td>\n";
 echo "<select name=$name"."y1>";
 if($y1 == 0){
 	echo"<option selected value=\"0000\">_</option>\n";
	}
 else{
 	echo"<option value=\"0000\">_</option>\n";
	}
 for($i = 2000;$i <= date("Y") + 5;$i++){
   if($y1 == $i) $s = "selected"; else $s = "";
   echo"<option $s>$i</option>\n";
  }
 echo "</select>\n";
 echo "</td>\n";
 echo "</tr>\n";
 echo "</table>\n";
 }


	$_1_2[1]="одна ";
	$_1_2[2]="дв╕ ";

	$_1_19[1]="одна ";
	$_1_19[2]="дв╕ ";
	$_1_19[3]="три ";
	$_1_19[4]="чотири ";
	$_1_19[5]="п'ять ";
	$_1_19[6]="ш╕сть ";
	$_1_19[7]="с╕м ";
	$_1_19[8]="в╕с╕м ";
	$_1_19[9]="дев'ять ";
	$_1_19[10]="десять ";

	$_1_19[11]="одинадцять ";
	$_1_19[12]="дванадцять ";
	$_1_19[13]="тринадцять ";
	$_1_19[14]="чотирнадцять ";
	$_1_19[15]="п'ятнадцять ";
	$_1_19[16]="ш╕стнадцять ";
	$_1_19[17]="с╕мнадцять ";
	$_1_19[18]="в╕с╕мнадцять ";
	$_1_19[19]="дев'ятнадцять ";

	$des[2]="двадцять ";
	$des[3]="тридцять ";
	$des[4]="сорок ";
	$des[5]="п'ятдесят ";
	$des[6]="ш╕стдесят ";
	$des[7]="с╕мдесят ";
	$des[8]="в╕с╕мдесят ";
	$des[9]="дев'яносто ";

	$hang[1]="сто ";
	$hang[2]="дв╕ст╕ ";
	$hang[3]="триста ";
	$hang[4]="чотириста ";
	$hang[5]="п'ятсот ";
	$hang[6]="ш╕стсот ";
	$hang[7]="с╕мсот ";
	$hang[8]="в╕с╕мсот ";
	$hang[9]="дев'ятсот ";

	$namerub[1]="гривня ";
	$namerub[2]="гривн╕ ";
	$namerub[3]="гривень ";

	$nametho[1]="тисяча ";
	$nametho[2]="тисяч╕ ";
	$nametho[3]="тисяч ";

	$namemil[1]="м╕льйон ";
	$namemil[2]="м╕льйона ";
	$namemil[3]="м╕льйон╕в ";

	$namemrd[1]="м╕льярд ";
	$namemrd[2]="м╕льярда ";
	$namemrd[3]="м╕льярд╕в ";

	$kopeek[1]="коп╕йка ";
	$kopeek[2]="коп╕йки ";
	$kopeek[3]="коп╕йок ";


function semantic($i,&$words,&$fem,$f){
	global $_1_2, $_1_19, $des, $hang, $namerub, $nametho, $namemil, $namemrd;
	$words="";
	$fl=0;
	if($i >= 100){
		$jkl = intval($i / 100);
		$words.=$hang[$jkl];
		$i%=100;
		}
	if($i >= 20){
		$jkl = intval($i / 10);
		$words.=$des[$jkl];
		$i%=10;
		$fl=1;
		}
	switch($i){
		case 1: $fem=1; break;
		case 2:
		case 3:
		case 4: $fem=2; break;
		default: $fem=3; break;
		}
	if($i){
		if($i < 3 && $f > 0){
			if($f >= 2){
				$words.=$_1_19[$i];
				}
			else{
				$words.=$_1_2[$i];
				}
			}
		else{
			$words.=$_1_19[$i];
			}
		}
}


function num2str($L){
	global $_1_2, $_1_19, $des, $hang, $namerub, $nametho, $namemil, $namemrd, $kopeek;

	$s=" ";
	$s1=" ";
	$s2=" ";
	$kop=intval((round0($L*100 - round0(intval($L)*100))));
	$L=intval($L);
	if($L >= 1000000000){
		$many=0;
 		semantic(intval($L / 1000000000),$s1,$many,3);
		$s .= $s1.$namemrd[$many];
		$L %= 1000000000;
 		}
	if($L >= 1000000){
		$many=0;
		semantic(intval($L / 1000000),$s1,$many,2);
		$s .= $s1 . $namemil[$many];
		$L %= 1000000;
		if($L == 0){
			$s.="гривень ";
			}
		}
	if($L >= 1000){
		$many=0;
		semantic(intval($L / 1000),$s1,$many,1);
		$s .= $s1 . $nametho[$many];
		$L %= 1000;
 		if($L==0){
 			$s.="гривень ";
 			}
		}
	if($L != 0){
		$many=0;
		semantic($L,$s1,$many,0);
		$s .= $s1 . $namerub[$many];
 		}
	if($kop > 0){
		$many=0;
		semantic($kop,$s1,$many,1);
		$s .= $s1 . $kopeek[$many];
		}
	else{
		$s .= " 0 коп╕йок";
 		}
	return $s;
}

require("fpdf.php");
//header("Pragma: public");
class PDF extends FPDF
{
//Page header
function Header()
{
    //Arial bold 15
    //$this->SetFont('arial','',15);
    //Move to the right
    //$this->Cell(80);
    //Title
    //$this->Cell(0, 0, 'Рахунок-фактура', 0, 1,'C');
    //Line break
    //$this->Ln(20);
		$this->SetAuthor("Aleksandr Kuzminsky");
		$this->SetCreator("NetStore Billing System");
		$this->SetSubject("Bill");
		$this->SetTitle("Bill for services");
}

//Page footer
function Footer()
{
    //Position at 1.5 cm from bottom
    $this->SetY(-15);
    //Arial italic 8
    $this->SetFont("arial","",8);
    //$this->Cell(0,0, "Copyright © 2002-2004 by Aleksandr Kuzminsky. All rights reserved.", 0, 0);
		//Page number
    //$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
}
}


function set_permissions($mysql, $operator, $level)
{
 	global $radius_db, $db;

	// First revoke all privileges
	$query = "UPDATE mysql.user SET
			Select_priv = 'N',
			Insert_priv = 'N',
			Update_priv = 'N',
			Delete_priv = 'N',
			Create_priv = 'N',
			Drop_priv = 'N',
			Reload_priv = 'N',
			Shutdown_priv = 'N',
			Process_priv = 'N',
			File_priv = 'N',
			Grant_priv = 'N',
			References_priv = 'N',
			Index_priv = 'N',
			Alter_priv = 'N'
			WHERE User = '$operator' AND Host = 'localhost'";
  if(FALSE == mysql_query($query, $mysql)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
	$query = "DELETE FROM mysql.db WHERE User = '$operator' AND Host = 'localhost'";
  if(FALSE == mysql_query($query, $mysql)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
	$query = "DELETE FROM mysql.tables_priv WHERE User = '$operator' AND Host = 'localhost'";
  if(FALSE == mysql_query($query, $mysql)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
	$query = "DELETE FROM mysql.columns_priv WHERE User = '$operator' AND Host = 'localhost'";
  if(FALSE == mysql_query($query, $mysql)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
	// Set premissions according to level
	switch($level){
    case "accountoperator":
      $query = "GRANT SELECT ON `$db`.* to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT UPDATE ON `$db`.`client` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      break;
    case "juniorsupport":
      $query = "GRANT SELECT ON `$db`.`client` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$db`.`client_cluster` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$db`.`cluster` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$db`.`client_network` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$db`.`client_interface` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$db`.`interfaces` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$db`.`feeding` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$db`.`traffic_snapshot` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$db`.`filter` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$db`.`filter_counter_snapshot` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$radius_db`.`radreply` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$radius_db`.`usergroup` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$db`.`permlevel` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$db`.`userlevel` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT(id, login, description, blocked, inactivation_time, activation_time, blocking_time) ON `$db`.`client` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT(id, client_id) ON `$db`.`contract` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT(id, contract_id, cash, tariff_id) ON `$db`.`service` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT(timestamp, value_without_vat, service_id) ON `$db`.`charge` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT(id, monthlypayment) ON `$db`.`tariff` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT(contract_id, timestamp, value_without_vat, cash) ON `$db`.`payment` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
			/*
      $query = "GRANT SELECT ON `$db`.* to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
			*/
      break;
    case "support":
      $query = "GRANT SELECT ON `$radius_db`.`radreply` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$radius_db`.`usergroup` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$db`.* to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT UPDATE ON `$db`.`client` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT UPDATE, INSERT, DELETE ON `$db`.`client_cluster` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT UPDATE ON `$db`.`cluster` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT INSERT ON `$db`.`order_report` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      break;
    case "admin":
      $query = "GRANT RELOAD, GRANT OPTION, FILE ON *.* to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT ALL ON `$db`.* to '$operator'@'localhost' WITH GRANT OPTION";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT ALL ON `$radius_db`.* to '$operator'@'localhost' WITH GRANT OPTION";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT ALL ON `mysql`.* to '$operator'@'localhost' WITH GRANT OPTION";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      break;
    case "topmanager":
      $query = "GRANT FILE ON *.* to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT INSERT, DELETE, UPDATE ON `$db`.`bill` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT INSERT, DELETE ON `$db`.`bill_item` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT INSERT, DELETE, UPDATE ON `$db`.`bill_value` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT INSERT, DELETE ON `$db`.`payment` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT UPDATE ON `$db`.`client` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
    case "manager":
      $query = "GRANT SELECT ON `$radius_db`.`radreply` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$radius_db`.`usergroup` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT SELECT ON `$db`.* to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT INSERT ON `$db`.`order_report` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT INSERT, DELETE ON `$db`.`order_report` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      $query = "GRANT INSERT, DELETE ON `$db`.`extract` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
      break;
    }
      $query = "GRANT INSERT ON `$db`.`log_event` to '$operator'@'localhost'";
      if(FALSE == mysql_query($query, $mysql)){
        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
        $msg = str_replace("\n", "<br>", $msg);
        $msg = urlencode($msg);
        header ("Location: show_error.php?error=".$msg);
        mysql_close($mysql);
        exit(1);
        }
  $query = "FLUSH PRIVILEGES";
  if(FALSE == mysql_query($query, $mysql)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
}

function delete_filter($mysql, $id, $start_transaction)
{
	if($start_transaction == "yes"){
		mysql_query("BEGIN", $mysql);
		}
	$id = mysql_escape_string($id);
	$query = "DELETE FROM filter WHERE id = '$id'";
  if(FALSE == mysql_query($query, $mysql)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
		mysql_close($mysql);
    exit(1);
    }
	$query = "DELETE FROM filter_action WHERE filter_id = '$id'";
  if(FALSE == mysql_query($query, $mysql)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
		mysql_close($mysql);
    exit(1);
    }
	$query = "DELETE FROM filter_counter WHERE filter_id = '$id'";
  if(FALSE == mysql_query($query, $mysql)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
		mysql_close($mysql);
    exit(1);
    }
	$query = "DELETE FROM filter_counter_snapshot WHERE filter_id = '$id'";
  if(FALSE == mysql_query($query, $mysql)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
		mysql_close($mysql);
    exit(1);
    }
	$query = "DELETE FROM filter_definition WHERE filter_id = '$id'";
  if(FALSE == mysql_query($query, $mysql)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
		mysql_close($mysql);
    exit(1);
    }
	if($start_transaction == "yes"){
		mysql_query("COMMIT", $mysql);
		}
}

function delete_filters($mysql, $client_id, $start_transaction)
{
	$client_id = mysql_escape_string($client_id);
	if($start_transaction == "yes"){
		mysql_query("BEGIN", $mysql);
		}
	$query = "SELECT id FROM filter WHERE client_id = '$client_id'";
  $result = mysql_query($query, $mysql);
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
		$filter_id = mysql_result($result, $i, "id");
		delete_filter($mysql, $filter_id, "no");
		}
	if($start_transaction == "yes"){
		mysql_query("COMMIT", $mysql);
		}
}

function delete_service($mysql, $id, $start_transaction)
{
	if($start_transaction == "yes"){
		mysql_query("BEGIN", $mysql);
		}
	$id = mysql_escape_string($id);
	$query = "SELECT tariff_id FROM service WHERE id = '$id'";
  $result = mysql_query($query, $mysql);
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
	if($n == 1){
		$tariff_id = mysql_result($result, 0, "tariff_id");
		$query = "DELETE FROM tariff WHERE id = '$tariff_id'";
		if(FALSE == mysql_query($query, $mysql)){
		  $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		  $msg = str_replace("\n", "<br>", $msg);
		  $msg = urlencode($msg);
		  header ("Location: show_error.php?error=".$msg);
		  mysql_query("ROLLBACK");
			mysql_close($mysql);
		  exit(1);
		  }
		}
	$query = "DELETE FROM service WHERE id = '$id'";
  if(FALSE == mysql_query($query, $mysql)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
		mysql_close($mysql);
    exit(1);
    }
	$query = "DELETE FROM charge WHERE service_id = '$id'";
  if(FALSE == mysql_query($query, $mysql)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
		mysql_close($mysql);
    exit(1);
    }
	if($start_transaction == "yes"){
		mysql_query("COMMIT", $mysql);
		}
}

function delete_services($mysql, $client_id, $start_transaction)
{
	$client_id = mysql_escape_string($client_id);
	if($start_transaction == "yes"){
		mysql_query("BEGIN", $mysql);
		}
	$query = "SELECT service.id FROM service LEFT JOIN contract ON contract.id = service.contract_id WHERE client_id = '$client_id'";
  $result = mysql_query($query, $mysql);
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
		$service_id = mysql_result($result, $i);
		delete_service($mysql, $service_id, "no");
		}
	if($start_transaction == "yes"){
		mysql_query("COMMIT", $mysql);
		}
}

function delete_contract($mysql, $id, $start_transaction)
{
	if($start_transaction == "yes"){
		mysql_query("BEGIN", $mysql);
		}
	$id = mysql_escape_string($id);
	// Get some info about contract
	$query = "SELECT *  FROM contract WHERE id = '$id'";
  $result = mysql_query($query, $mysql);
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
	if($n == 0){
		$msg = "Договору з ╕дентиф╕катором $id в баз╕ нема╓.";
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
		mysql_close($mysql);
    exit(1);
		}
	$description = mysql_result($result, 0, "description");
	$c_type = mysql_result($result, 0, "c_type");
	$c_number = mysql_result($result, 0, "c_number");
	// Check for any referances to contract
	$query = "SELECT count(*) AS num FROM bill WHERE contract_id = '$id'";
  $result = mysql_query($query, $mysql);
	if(FALSE == $result){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
		mysql_close($mysql);
    exit(1);
    }
	$num = mysql_result($result, 0, "num");
	if($num != 0){
		$msg = "Неможливо видалити догов╕р($c_type-$c_number: $description),
						оск╕льки в баз╕ ╓ рахунки, що посилаються на цей догов╕р.
						Видал╕ть спочатку ╖х,
						а пот╕м можна буде видалити сам догов╕р.";
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		mysql_query("ROLLBACK");
		mysql_close($mysql);
		exit(1);
		}
	$query = "SELECT count(*) AS num FROM payment WHERE contract_id = '$id'";
  $result = mysql_query($query, $mysql);
	if(FALSE == $result){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
		mysql_close($mysql);
    exit(1);
    }
	$num = mysql_result($result, 0, "num");
	if($num != 0){
		$msg = "Неможливо видалити догов╕р($c_type-$c_number: $description),
						оск╕льки в баз╕ ╓ платеж╕, що посилаються на цей догов╕р.
						Видал╕ть спочатку ╖х,
						а пот╕м можна буде видалити сам догов╕р.";
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		mysql_query("ROLLBACK");
		mysql_close($mysql);
		exit(1);
		}
	$query = "SELECT count(*) AS num FROM service WHERE contract_id = '$id'";
  $result = mysql_query($query, $mysql);
	if(FALSE == $result){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
		mysql_close($mysql);
    exit(1);
    }
	$num = mysql_result($result, 0, "num");
	if($num != 0){
		$msg = "Неможливо видалити догов╕р($c_type-$c_number: $description),
						оск╕льки в баз╕ ╓ послуги, що посилаються на цей догов╕р.
						Видал╕ть спочатку ╖х,
						а пот╕м можна буде видалити сам догов╕р.";
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		mysql_query("ROLLBACK");
		mysql_close($mysql);
		exit(1);
		}
	// Delete contract
	$query = "DELETE FROM contract WHERE id = '$id'";
  if(FALSE == mysql_query($query, $mysql)){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_query("ROLLBACK");
		mysql_close($mysql);
    exit(1);
    }
	if($start_transaction == "yes"){
		mysql_query("COMMIT", $mysql);
		}
}

function delete_contracts($mysql, $client_id, $start_transaction)
{
	$client_id = mysql_escape_string($client_id);
	if($start_transaction == "yes"){
		mysql_query("BEGIN", $mysql);
		}
	$query = "SELECT id FROM contract WHERE client_id = '$client_id'";
  $result = mysql_query($query, $mysql);
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
		$contract_id = mysql_result($result, $i, "id");
		delete_contract($mysql, $contract_id, "no");
		}
	if($start_transaction == "yes"){
		mysql_query("COMMIT", $mysql);
		}
}

function print_bill($mysql, $bill, $num_copies, $save_name, $inline, $send_pdf, $stamp)
{
	global $TMPDIR;
	if(is_array($bill)){
		$bill_id = $bill[0];
		$ids = array_values($bill);	
		}
	else{
		$ids = array($bill);
		}
	$pdf=new PDF("P", "mm", "A4");
	$pdf->AddFont("arial","","arial.php");
	$pdf->AddFont("arialbd","B","arialbd.php");

	$pdf->Open();
	foreach($ids as $bill_id){
		$bill_id = mysql_escape_string($bill_id);
		mysql_query("BEGIN");
		$query = "SELECT IF(pdf_body IS NOT NULL, 1, 0) AS pdf_present
				FROM bill
				WHERE id = '$bill_id'";
		$result = mysql_query($query, $mysql);
		if($result == FALSE){
			$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK", $mysql);
			mysql_close($mysql);
			exit(1);
			}
		$pdf_present = mysql_result($result, 0, "pdf_present");
		if($pdf_present == 1){
		  $query = "SELECT
					pdf_body
					FROM bill
					WHERE id = '$bill_id'";
		  $result = mysql_query($query, $mysql);
			if($result == FALSE){
				$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".$msg);
				mysql_query("ROLLBACK", $mysql);
				mysql_close($mysql);
				exit(1);
				}
		  $n = mysql_num_rows($result);
			if($n == 1){
				$pdf_body = mysql_result($result, 0, "pdf_body");
				header("Content-Type: application/pdf");
				header("Content-Length: ".strlen($pdf_body));
				echo $pdf_body;
				}
			exit(0);
			}
		$query = "SELECT
				DATE_FORMAT(ADDDATE(bill.timestamp, INTERVAL 1 MONTH), '%Y-%m-%d') AS bill_actual_timestamp,
				UNIX_TIMESTAMP(bill.create_timestamp) AS create_timestamp,
				bill.bill_num,
				bill_value.*
				FROM bill
				LEFT JOIN bill_value ON bill_value.bill_id = bill.id
				WHERE bill.id = '$bill_id'";
		$result = mysql_query($query, $mysql);
		if($result == FALSE){
			$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK", $mysql);
			mysql_close($mysql);
			exit(1);
			}
		if(mysql_num_rows($result) == 1){
			$bill_num = mysql_result($result, 0, "bill.bill_num");
			$bill_actual_timestamp = mysql_result($result, 0, "bill_actual_timestamp");
			$create_timestamp = strftime("%d %B %Y", mysql_result($result, 0, "create_timestamp"));
			$provider = mysql_result($result, 0, "provider");
			$payer = mysql_result($result, 0, "payer");
			$base = mysql_result($result, 0, "base");
			$saldo_without_vat = mysql_result($result, 0, "saldo_without_vat");
			$saldo_vat = mysql_result($result, 0, "saldo_vat");
			$saldo_with_vat = mysql_result($result, 0, "saldo_with_vat");
			$in_all_without_vat = mysql_result($result, 0, "in_all_without_vat");
			$in_all_vat = mysql_result($result, 0, "in_all_vat");
			$in_all_with_vat = mysql_result($result, 0, "in_all_with_vat");
			$to_order_without_vat = mysql_result($result, 0, "to_order_without_vat");
			$to_order_vat = mysql_result($result, 0, "to_order_vat");
			$to_order_with_vat = mysql_result($result, 0, "to_order_with_vat");
			$to_order_str = mysql_result($result, 0, "to_order_str");
			$operator_name = mysql_result($result, 0, "operator_name");
			}
		else{
			$msg = "Нев╕домий ╕дентиф╕катор рахунку '$bill_id'";
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK", $mysql);
			mysql_close($mysql);
			exit(1);
			}
		$pdf->AddPage();
		for($u = 0; $u < $num_copies; $u++){
			if($pdf->GetY() > 148){
				$pdf->AddPage();
				}
			//Logo
			$y = $pdf->GetY();
			$pdf->Image("img/nbi_logo.png", 10, $y, 33);
			$pdf->SetFont("arial", "", 10);
			$pdf->SetX(45);
			$str = "Постачальник";
			$size = $pdf->GetStringWidth($str) + 4;
			$pdf->Cell($size, 4, $str, 0, 0);
			$pdf->SetFontSize(10);
			$str = $provider;
			$pdf->MultiCell(0, 4, $str, 0, "L");
			$pdf->SetX(45);
			$str = "Платник";
			$pdf->Cell($size, 4, $str, 0, 0);
			$str = $payer;
			$pdf->MultiCell(0, 4, $str, 0, "L");
			$pdf->SetX(45);
			$str = "П╕дстава";
			$pdf->Cell($size, 4, $str, 0, 0);
			$str = $base;
			$pdf->MultiCell(0, 4, $str, 0, "L");
			$pdf->Ln();
			$pdf->SetFont("arialbd", "B", 14);
			$str = "Рахунок-фактура #".sprintf("%08u",$bill_num)."\nв╕д ".$create_timestamp;
			$pdf->MultiCell(0, 6, $str, 0, "C");
		
			if($saldo_without_vat < 0){
				$str = "ЗАБОРГОВАН╤СТЬ (без ПДВ)";
				}
			else{
				$str = "ЗАЛИШОК (без ПДВ)";
				}
			$str .= " на ".$bill_actual_timestamp;
			$pdf->SetFont("arial", "", 10);
			$pdf->Cell(157, 4, $str, 1, 0, "L", 0);
			
			$str = number_format(abs($saldo_without_vat), 2, ".", " ");
			$pdf->SetFont("arialbd", "B", 10);
			$pdf->Cell(0, 4, $str, 1, 1, "R", 0);
			// Table header
			$pdf->SetFont("arial", "", 10);
			$pdf->SetFillColor(200, 200, 200);
			$str = "НАРАХОВАНО:";
			$pdf->Cell(0, 4, $str, 1, 1, "L", 1);
		
			$query = "SELECT
					description,
					measure,
					kolvo,
					price
					FROM bill_item
					WHERE bill_id = '$bill_id'";
			$result = mysql_query($query, $mysql);
			if($result == FALSE){
				$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
				$msg = str_replace("\n", "<br>", $msg);
				$msg = urlencode($msg);
				header ("Location: show_error.php?error=".$msg);
				mysql_query("ROLLBACK", $mysql);
				mysql_close($mysql);
				exit(1);
				}
			$n = mysql_num_rows($result);
			$pdf->SetFont("arial", "", 10);
			$pdf->SetFillColor(255, 255, 255);
			for($i = 0; $i < $n; $i++){
				$description = mysql_result($result, $i, "description");
				$price = mysql_result($result, $i, "price");
				$str = $i + 1;
				$pdf->Cell(7, 4, $str, 1, 0, "C", 0);
				$str = $description;
				$pdf->Cell(150, 4, $str, 1, 0, "L", 0);
				$str = number_format($price, 2, ".", " ");
				$pdf->Cell(0, 4, $str, 1, 1, "R", 0);
				}
			$str = "Разом без ПДВ:";
			$pdf->Cell(157, 4, $str, 0, 0, "R", 0);
			$str = number_format($in_all_without_vat, 2, ".", " ");
			$pdf->Cell(0, 4, $str, 1, 1, "R", 0);
			$str = "ПДВ:";
			$pdf->Cell(157, 4, $str, 0, 0, "R", 0);
			$str = number_format($in_all_vat, 2, ".", " ");
			$pdf->Cell(0, 4, $str, 1, 1, "R", 0);
			$str = "Всього з ПДВ:";
			$pdf->Cell(157, 4, $str, 0, 0, "R", 0);
			$str = number_format($in_all_with_vat, 2, ".", " ");
			$pdf->SetFont("arialbd", "B", 10);
			$pdf->Cell(0, 4, $str, 1, 1, "R", 0);
			//$pdf->Cell(0, 2, "", 1, 1, "L", 1);
			$pdf->SetFont("arial", "", 10);
			if($stamp){
				$y = $pdf->GetY();
				$pdf->Image("img/invoice_stamp.png", 100, $y , 80);
				}
			$str = "ДО СПЛАТИ:";
			$pdf->Cell(0, 4, $str, 1, 1, "L", 1);
		
			$pdf->SetFont("arial", "", 10);
			$str = "без ПДВ";
			$pdf->Cell(157, 4, $str, 1, 0, "L", 0);
			$str = number_format($to_order_without_vat, 2, ".", " ");
			$pdf->Cell(0, 4, $str, 1, 1, "R", 0);
		
			$str = "ПДВ";
			$pdf->Cell(157, 4, $str, 1, 0, "L", 0);
			$str = number_format($to_order_vat, 2, ".", " ");
			$pdf->Cell(0, 4, $str, 1, 1, "R", 0);
		
			$str = "Разом з ПДВ";
			$pdf->Cell(157, 4, $str, 1, 0, "L", 0);
			$str = number_format($to_order_with_vat, 2, ".", " ");
			$pdf->SetFont("arialbd", "B", 10);
			$pdf->Cell(0, 4, $str, 1, 1, "R", 0);
		
			$pdf->SetFont("arial", "", 10);
			$str = "До сплати сума:";
			$pdf->Cell(157, 4, $str, 0, 1, "L", 0);
			$str = $to_order_str;
			$pdf->SetFont("arialbd", "B", 10);
			$pdf->Cell(157, 4, $str, 0, 1, "L", 0);
			$pdf->SetFont("arial", "", 10);
			$pdf->Ln();
			$str = $operator_name;
			$pdf->Cell(0, 4, $str, 0, 0, "L", 0);
			$x = $pdf->GetX();
			$y = $pdf->GetY();
			$pdf->Ln();
			$pdf->Ln();
			$pdf->Ln();
			$pdf->Line(0, $y, $x, $y);
			$pdf->Ln();
			}
		}
	//header("Content-Type: application/pdf");
	//header("Content-Length: ".strlen($pdf_body));
	$file = tempnam($TMPDIR, "netstore.".session_id());
	$pdf->Output($file, false);
	//$pdf->Output();
	chmod($file, 0644);
	if($send_pdf){
		if($save_name == ""){
			$save_name = "netstore_bill.pdf";
			}
		if($inline != "inline"){
			header("Content-disposition: attachment; filename=".$save_name);
			}
		header("Content-Type: application/pdf");
		header("Content-Length: ".filesize($file));
		readfile($file);
		unlink($file);
		}
	return $file;
}
  

function add_bill($mysql, $contract_id, $y, $m)
{
  global $login;
	mysql_query("BEGIN", $mysql);
	$vat = get_vat($mysql);
  $contract_id = mysql_escape_string($contract_id);
  $y = mysql_escape_string($y);
  $m = mysql_escape_string($m);
  // Считаем задолженность на начало периода.
  // Считаем крЕдит
  if($m == 12){
    $y1 = $y + 1;
    $m1 = 1;
    }
  else{
    $y1 = $y;
    $m1 = $m + 1;
    }
  if($m1 == 12){
    $y2 = $y1 + 1;
    $m2 = 1;
    }
  else{
    $y2 = $y1;
    $m2 = $m1 + 1;
    }
  $query = "SELECT `value` FROM `config` WHERE `attribute` = 'tac'";
  $result = mysql_query($query, $mysql);
	if($result == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
    }
	if(mysql_num_rows($result) == 1){
		$tax = mysql_result($result, 0, "value");
		}
	else{
		$tax = 0.2;
		}
	// Считаем кредит
  $query = "SELECT 
			IFNULL(SUM(`charge`.`value_without_vat`), 0) AS `kredit_without_vat`
      FROM `charge`
      LEFT JOIN `service` ON `service`.`id` = `charge`.`service_id`
      WHERE `service`.`contract_id` = '$contract_id'
      AND `charge`.`timestamp` < '".$y."-".$m."-01'
			AND `service`.`cash` = 'no'";
	$result = mysql_query($query, $mysql);
	if($result == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
    }
  if(mysql_num_rows($result) == 1){
    $kredit_without_vat = mysql_result($result, 0, "kredit_without_vat");
    }
  // Счтитаем часть кредита, которую клиент платит наперед
  
	$query = "SELECT
			IFNULL(SUM(`charge`.`value_without_vat`), 0) AS `kredit_without_vat`
      FROM `charge`
      LEFT JOIN `service` ON `service`.`id` = `charge`.`service_id`
      LEFT JOIN `tariff` ON `tariff`.`id` = `service`.`tariff_id`
      WHERE `service`.`contract_id` = '$contract_id'
      AND `charge`.`timestamp` >= '".$y."-".$m."-01'
      AND `charge`.`timestamp` < '".$y1."-".$m1."-01'
      AND `tariff`.`monthlypayment` = 'yes'
			AND `service`.`cash` = 'no'";
  $result = mysql_query($query, $mysql);
  if($result == FALSE){
    $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
    $msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
    }
  if(mysql_num_rows($result) == 1){
    $kredit_without_vat = round2($kredit_without_vat + mysql_result($result, 0, "kredit_without_vat"));
    }
	
  // Считаем дЕбет
  $query = "SELECT IFNULL(SUM(`value_without_vat`), 0) AS `debet_without_vat`
      FROM `payment`
      WHERE `contract_id` = '$contract_id'
      AND `payment`.`timestamp` < '".$y1."-".$m1."-01'
			AND `payment`.`cash` = 'no'";
  $result = mysql_query($query, $mysql);
  if($result == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
    }
  if(mysql_num_rows($result) == 1){
    $debet_without_vat = mysql_result($result, 0, "debet_without_vat");
    }
	$saldo_without_vat = round2($debet_without_vat - $kredit_without_vat);
  // Создаем в базе запись для счета
  // Определяем номер для счета
	$query = "SELECT `value` AS `bill_num` FROM `counter` WHERE `variable` = 'bill_num' FOR UPDATE";
  $result = mysql_query($query, $mysql);
  if($result == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
    }
	$bill_num = 1;
  if(mysql_num_rows($result) == 1){
    $bill_num = mysql_result($result, 0, "bill_num");
    }
	else{ // Счетчик номера счета не существует
		$query = "INSERT INTO `counter` ( `variable` , `value` ) VALUES ( 'bill_num', '1')"; 
		if(mysql_query($query, $mysql) == FALSE){
			$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
		  if(!headers_sent()){
				header ("Location: show_error.php?error=".$msg);
				}
			else{
				echo urldecode($msg);
				}
		  mysql_query("ROLLBACK", $mysql);
		  mysql_close($mysql);
		  exit(1);
		  }
		}
	// Увеличиваем счетчик номеров на 1
	$query = "UPDATE `counter` SET `value` = `value` + 1 WHERE `variable` = 'bill_num'";
	if(mysql_query($query, $mysql) == FALSE){
		$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
	  if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
	  mysql_query("ROLLBACK", $mysql);
	  mysql_close($mysql);
	  exit(1);
	  }
	// Собственно, добавляем запись
	$query = "INSERT INTO bill(bill_num, contract_id, timestamp, create_timestamp, operator)
      VALUES('$bill_num', '$contract_id', '$y-$m-01', NOW(), '$login')";
  if(mysql_query($query, $mysql) == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
    }
  $bill_id = mysql_insert_id($mysql);
  // Получаем список сервисов для данного договора
  $query = "SELECT
      service.id AS id,
      service.description AS description,
      service.short_description AS short_description,
      UNIX_TIMESTAMP(service.start_time) AS start_time,
      UNIX_TIMESTAMP(service.expire_time) AS expire_time,
      UNIX_TIMESTAMP('".$y."-".$m."-01') AS beg_period,
      UNIX_TIMESTAMP('".$y2."-".$m2."-01') AS end_period,
      tariff.monthlypayment AS monthlypayment
      FROM service 
      LEFT JOIN tariff on tariff.id = service.tariff_id 
      WHERE contract_id = '$contract_id'
			AND service.cash = 'no'";
	//mail("ingoth@nbi.com.ua", "[NetStore Debug] Query:", $query);
	$result = mysql_query($query, $mysql);
  if($result == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
    }
  $n = mysql_num_rows($result);
  // Общая сума - сальдо + то, что наработал(вместе с абонплатой)
  $service_id = array();
	$in_all_without_vat = 0;
	$in_all_vat = 0;
	$in_all_with_vat = 0;
	for($i = 0; $i < $n; $i++){
    $id = mysql_result($result, $i, "id");
    $description = mysql_result($result, $i, "description");
    $short_description = mysql_result($result, $i, "short_description");
    $start_time = mysql_result($result, $i, "start_time");
    $expire_time = mysql_result($result, $i, "expire_time");
    $beg_period = mysql_result($result, $i, "beg_period");
    $end_period = mysql_result($result, $i, "end_period");
    $monthlypayment = mysql_result($result, $i, "monthlypayment");
    if($monthlypayment == "yes"){
			$ts1 = mktime(0, 0, 0, $m1, 1, $y1);
			$ts2 = mktime(0, 0, 0, $m2, 1, $y2);
			}
		else{
			$ts1 = mktime(0, 0, 0, $m, 1, $y);
			$ts2 = mktime(0, 0, 0, $m1, 1, $y1);
			}
		if($expire_time != 0 && $expire_time <= $ts1){
      continue;
      }
		if($start_time != 0 && $start_time >= $ts2){
      continue;
      }
		if($short_description != ""){
			$description .= "(". $short_description.")";
    	}
		if($monthlypayment == "no"){
      $from = "'$y-$m-01'";
      $to = "'$y1-$m1-01'";
			$description .= " за ".strftime("%OB %Y", mktime(0, 0, 0, $m, 1, $y));
      }
    else{
      $from = "'$y1-$m1-01'";
      $to = "'$y2-$m2-01'";
			$description .= " за ".strftime("%OB %Y", mktime(0, 0, 0, $m1, 1, $y1));
      }
    $q = "SELECT IFNULL(SUM(`value_without_vat`), 0) AS without_vat
				FROM `charge`
        WHERE `service_id` = '$id'
        AND `timestamp` >= ".$from."
        AND `timestamp` < ".$to;
    $r = mysql_query($q, $mysql);
    $without_vat = mysql_result($r, 0, "without_vat");
    $in_all_without_vat = round2($in_all_without_vat + mysql_result($r, 0, "without_vat"));
		$q_bill_item = "insert into bill_item(bill_id, description, price)
        values($bill_id, '$description', '$without_vat')";
    if(FALSE == mysql_query($q_bill_item, $mysql)){
  		$msg = "Error: ".mysql_error()." while executing:\n".$q;
  		$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
    	if(!headers_sent()){
				header ("Location: show_error.php?error=".$msg);
				}
			else{
				echo urldecode($msg);
				}
      mysql_query("ROLLBACK", $mysql);
      mysql_close($mysql);
      exit(1);
      }
    }
	$in_all_with_vat = round2($in_all_without_vat * (1 + $vat));
	$in_all_vat = round2($in_all_with_vat - $in_all_without_vat);
	if($in_all_without_vat < $saldo_without_vat){
		$to_order_without_vat =  0;
		$to_order_vat = 0;
		$to_order_with_vat = 0;
		}
	else{
		$to_order_without_vat = round2($in_all_without_vat - $saldo_without_vat);
		$to_order_with_vat = round2($to_order_without_vat * (1 + $vat));
		$to_order_vat = round2($to_order_with_vat - $to_order_without_vat);
		}
	$to_order_str = mysql_escape_string(num2str($to_order_with_vat));
	$provider = mysql_escape_string("ВАТ \"Нац╕ональне Бюро ╤нформац╕╖\"\n╢ДРПОУ 30112808\nP/p 260083161601 в ПАТ \"Аграрний комерц╕йний банк\" МФО 322302\n╤ПН 301128026543, номер св╕доцтва 36729125\nАдреса 03150, м. Ки╖в, вул. Велика Васильк╕вська, 106, оф. 2\ne-mail: info@nbi.ua\nhttp://www.nbi.ua");
  // Определяем номер договора
	$query = "SELECT
			contract.c_type,
			contract.c_number,
			UNIX_TIMESTAMP(contract.start_time) AS start_time,
			client.full_name,
			client.phone
			FROM contract
			LEFT JOIN client ON client.id = contract.client_id
			WHERE contract.id = '$contract_id'";
	$result = mysql_query($query, $mysql);
  if($result == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
 	 	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
   	mysql_query("ROLLBACK", $mysql);
   	mysql_close($mysql, $mysql);
   	exit(1);
   	}
	$payer = mysql_result($result, 0, "client.full_name")."\nтел. ".mysql_result($result, 0, "client.phone");
	$payer = mysql_escape_string($payer);
	$c_type = mysql_result($result, 0, "contract.c_type");
	$c_number = mysql_result($result, 0, "contract.c_number");
	$contract_date = strftime("%d %B %Y", mysql_result($result, 0, "start_time"));
	$base = mysql_escape_string("Догов╕р ".$c_type."-".$c_number.", в╕д ".$contract_date);
  // Определяем оператора, выписавшего счет
	$query = "SELECT
			name,
			phone
			FROM userlevel
			WHERE user = '$login'";
	$result = mysql_query($query, $mysql);
  if($result == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
 	 	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
   	mysql_query("ROLLBACK", $mysql);
   	mysql_close($mysql, $mysql);
   	exit(1);
   	}
	$operator = mysql_result($result, 0, "name");
	$operator_phone = mysql_result($result, 0, "phone");
	$str = "Виписав(ла): ".$operator." тел. ".$operator_phone;
	$operator_name = mysql_escape_string($str);	
	$query = "INSERT INTO `bill_value`(
			`bill_id`, 
			`provider`,
			`payer`,
			`base`,
			`saldo_without_vat`,
			`saldo_vat`,
			`saldo_with_vat`,
			`in_all_without_vat`,
			`in_all_vat`,
			`in_all_with_vat`,
			`to_order_without_vat`,
			`to_order_vat`,
			`to_order_with_vat`,
			`to_order_str`,
			`operator_name`)
			VALUES(
			'$bill_id', 
			'$provider',
			'$payer',
			'$base',
			'$saldo_without_vat',
			'$saldo_vat',
			'$saldo_with_vat',
			'$in_all_without_vat',
			'$in_all_vat',
			'$in_all_with_vat',
			'$to_order_without_vat',
			'$to_order_vat',
			'$to_order_with_vat',
			'$to_order_str',
			'$operator_name')";
  if(FALSE == mysql_query($query, $mysql)){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql, $mysql);
    exit(1);
    }
  mysql_query("COMMIT", $mysql);
	return $bill_id;
}

function delete_bill($mysql, $bill)
{
	if(is_array($bill)){
		$bill_id = $bill[0];
		$ids = array_values($bill);
		}
	else{
		$ids = array($bill);
		}
	mysql_query("BEGIN", $mysql);
	foreach($ids as $bill_id){
		$bill_id = mysql_escape_string($bill_id);
		$query = "DELETE FROM bill WHERE id = '$bill_id'";
		if(mysql_query($query, $mysql) == FALSE){
		  $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		  $msg = str_replace("\n", "<br>", $msg);
		  $msg = urlencode($msg);
		  header ("Location: show_error.php?error=".$msg);
		  mysql_query("ROLLBACK", $mysql);
		  mysql_close($mysql);
		  exit(1);
		  }
		$query = "DELETE FROM bill_item WHERE bill_id = '$bill_id'";
		if(mysql_query($query, $mysql) == FALSE){
		  $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		  $msg = str_replace("\n", "<br>", $msg);
		  $msg = urlencode($msg);
		  header ("Location: show_error.php?error=".$msg);
		  mysql_query("ROLLBACK", $mysql);
		  mysql_close($mysql);
		  exit(1);
		  }
		$query = "DELETE FROM bill_value WHERE bill_id = '$bill_id'";
		if(mysql_query($query, $mysql) == FALSE){
		  $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		  $msg = str_replace("\n", "<br>", $msg);
		  $msg = urlencode($msg);
		  header ("Location: show_error.php?error=".$msg);
		  mysql_query("ROLLBACK", $mysql);
		  mysql_close($mysql);
		  exit(1);
		  }
		$query = "DELETE FROM notification WHERE bill_id = '$bill_id'";
		if(mysql_query($query, $mysql) == FALSE){
		  $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		  $msg = str_replace("\n", "<br>", $msg);
		  $msg = urlencode($msg);
		  header ("Location: show_error.php?error=".$msg);
		  mysql_query("ROLLBACK", $mysql);
		  mysql_close($mysql);
		  exit(1);
		  }
		}
  mysql_query("COMMIT", $mysql);
}

// get_vat returns value of VAT tax stored in config

function get_vat($mysql)
{
	$query = "SELECT value FROM config WHERE attribute = 'vat'";
	$result = mysql_query($query);
	if($result == FALSE){
		$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
		header ("Location: show_error.php?error=".$msg);
		mysql_close($mysql);
		exit(1);
		}
	if(1 == mysql_num_rows($result)){
		return mysql_result($result, 0, "value");
		}
	else{
		return 0.2;
		}
}

function add_notification($mysql, $bill_id, $y, $m, $d)
{
	mysql_query("BEGIN", $mysql);
  $bill_id = mysql_escape_string($bill_id);
  $y = mysql_escape_string($y);
  $m = mysql_escape_string($m);
  $d = mysql_escape_string($d);

	$query = "SELECT IFNULL(MAX(num) + 1, 1) AS num FROM notification WHERE month_num = '".sprintf("%04u%02u",$y, $m)."'";
  $result = mysql_query($query, $mysql);
	if($result == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
    }
	$n = mysql_result($result, 0, "num");
	$header = "ВАТ \"Нац╕ональне Бюро ╤нформац╕╖\"\n03150, м. Ки╕в,\nвул. Велика Васильк╕вська, 106, оф. 2\nhttp://www.nbi.ua\ne-mail: info@nbi.ua\n\nтел. 38 044 201 02 03";
	$ut = mktime(0, 0, 0, $m, $d, $y);
	$sub_header1 = "Вих. #11-".sprintf("%04u-%02u-%u",$y, $m, $n)." в╕д ".strftime("%d %B %Yр.", $ut);
	$query = "SELECT client.full_name,
			bill.bill_num
			FROM bill
			LEFT JOIN contract ON bill.contract_id = contract.id
			LEFT JOIN client ON contract.client_id = client.id
			WHERE bill.id = '$bill_id'";
  $result = mysql_query($query, $mysql);
	if($result == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
    }
	$description = mysql_result($result, 0, "client.full_name");
	$bill_num = mysql_result($result, 0, "bill.bill_num");
	$sub_header2 = "Кер╕внику\n$description\n";
	$m_prev = ($m == 1) ? 12 : $m - 1;
	$month = strftime("%OB", mktime(0, 0, 0, $m_prev, 1, 2000));
	$body = "Направля╓мо Вам документи зг╕дно перел╕ку:\n1) Акт прийому-передач╕ послуг ╤нтернет за ".$month." - 2 прим. на 1 арк.;\n2) Податкова накладна за ".$month." - 1 прим. на 1 арк.;\n3) Рахунок-фактура #".sprintf("%08u",$bill_num)." - 1 прим. на 1 арк.\nПросимо протягом трьох робочих дн╕в п╕дписати та повернути на нашу адресу\nакт прийому-передач╕ послуг ╤нтернет.";
	$footer = "З повагою,\n Голова Правл╕ння                                                ╤.М. Оксанич";
	
	$header = mysql_escape_string($header);
	$sub_header1 = mysql_escape_string($sub_header1);
	$sub_header2 = mysql_escape_string($sub_header2);
	$body = mysql_escape_string($body);
	$footer = mysql_escape_string($footer);
	$query = "INSERT INTO `notification`(
			`bill_id`, 
			`month_num`,
			`num`,
			`header`,
			`sub_header1`,
			`sub_header2`,
			`body`,
			`footer`)
			VALUES(
			'$bill_id', 
			'".sprintf("%04u%02u",$y, $m)."',
			'$n',
			'$header',
			'$sub_header1',
			'$sub_header2',
			'$body',
			'$footer')";
  if(FALSE == mysql_query($query, $mysql)){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql, $mysql);
    exit(1);
    }
	$notification_id = mysql_insert_id($mysql);
  mysql_query("COMMIT", $mysql);
	return $notification_id;
}

function print_notification($mysql, $notification, $num_copies, $save_name, $inline, $send_pdf)
{
	global $TMPDIR;
	if(is_array($notification)){
		$notification_id = $notification[0];
		$ids = array_values($notification);	
		}
	else{
		$ids = array($notification);
		}
	$pdf=new PDF("L", "mm", "A5");
	$pdf->AddFont("arial","","arial.php");
	$pdf->AddFont("arialbd","B","arialbd.php");

	$pdf->Open();
	mysql_query("BEGIN");
	foreach($ids as $notification_id){
		$notification_id = mysql_escape_string($notification_id);
		$query = "SELECT * FROM notification WHERE id = '$notification_id'";
		//echo $query;
		//exit(0);
		$result = mysql_query($query, $mysql);
		if($result == FALSE){
			$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK", $mysql);
			mysql_close($mysql);
			exit(1);
			}
		if(mysql_num_rows($result) == 1){
			$header = mysql_result($result, 0, "header");
			$sub_header1 = mysql_result($result, 0, "sub_header1");
			$sub_header2 = mysql_result($result, 0, "sub_header2");
			$body = mysql_result($result, 0, "body");
			$footer = mysql_result($result, 0, "footer");
			}
		else{
			$msg = "Нев╕домий ╕дентиф╕катор пов╕домлення '$notification_id'";
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK", $mysql);
			mysql_close($mysql);
			exit(1);
			}
		$pdf->AddPage();
		for($u = 0; $u < $num_copies; $u++){
			if($pdf->GetY() > 148){
				$pdf->AddPage();
				}
			//Logo
			$y = $pdf->GetY();
			$pdf->Image("img/nbi_logo.png", 10, $y, 33);
			$pdf->SetFont("arial", "", 10);
			$pdf->SetX(90);
			$pdf->MultiCell(0, 4, $header, 0, "L");
			$pdf->Ln();
			$y = $pdf->GetY();
			$pdf->MultiCell(0, 4, $sub_header1, 0, "L");
			$pdf->SetY($y);
			$pdf->SetX(100);
			$pdf->SetFont("arialbd", "B", 10);
			$pdf->MultiCell(0, 4, $sub_header2, 0, "L");
			$pdf->SetFont("arial", "", 10);
			$pdf->Ln();
			$pdf->MultiCell(0, 4, $body, 0, "L");
			$pdf->Ln();
			$pdf->Ln();
			$pdf->Ln();
			$y = $pdf->GetY();
			$pdf->Image("img/sign_nechaeva.png", 60, $y - 20, 50);
			$pdf->MultiCell(0, 6, $footer, 0, "L");
			$pdf->Ln();
			$pdf->Ln();
			$y = $pdf->GetY();
			$pdf->Line(0, $y, 210, $y);
			$pdf->Ln();						 
			}
		}
	mysql_query("COMMIT");
	//header("Content-Type: application/pdf");
	//header("Content-Length: ".strlen($pdf_body));
	$file = tempnam($TMPDIR, "netstore.".session_id());
	$pdf->Output($file, false);
	//$pdf->Output();
	chmod($file, 0644);
	if($send_pdf){
		if($save_name == ""){
			$save_name = "notification.pdf";
			}
		if($inline != "inline"){
			header("Content-disposition: attachment; filename=".$save_name);
			}
		else{
			header("Content-disposition: inline; filename=".$save_name);
			}
		header("Content-Type: application/pdf");
		header("Content-Length: ".filesize($file));
		readfile($file);
		unlink($file);
		}
	return $file;
}

function mail_attach($from, $to, $subject, $notice, $file_path, $file_name)
{
	$fileatt_type = shell_exec("file -ib ".$file_path); //File Type
	$fileatt_type = str_replace("\n", "", $fileatt_type);
	$fileatt_name = $file_name;
	$email_from = $from; // Who the email is from
	$email_subject = $subject; // The Subject of the email
	$email_txt = $notice; // Message that the email has in it
			 
	$email_to = $to; // Who the email is too
	$headers = "From: ".$email_from;
					 
	$file = fopen($file_path, "r");
	$data = fread($file, filesize($file_path));
	fclose($file);
							  
	$semi_rand = md5(time());
	$mime_boundary = "==Multipart_Boundary_x".$semi_rand."x";
									 
	$headers .= "\nMIME-Version: 1.0\n" .
			"Disposition-Notification-To: ".$from."\n" .
			"Content-Type: multipart/mixed;\n" .
			" boundary=\"".$mime_boundary."\"";
	$email_message .= "This is a multi-partmessage in MIME format.\n\n" .
			"--".$mime_boundary."\n" .
			"Content-Type:text/plain; charset=\"koi8-u\"\n" .
			"Content-Transfer-Encoding: 8bit\n\n" .
			$email_txt . "\n\n";
	$data = chunk_split(base64_encode($data));
	$email_message .= "--".$mime_boundary."\n" .
			"Content-Type: ".$fileatt_type.";\n" .
			" name=\"".$fileatt_name."\"\n" .
			"Content-Disposition: inline;\n" .
			" filename=\"".$fileatt_name."\"\n" .
			"Content-Transfer-Encoding: base64\n\n" .
			$data . "\n\n" .
			"--".$mime_boundary."--\n";
			$email_message = $email_message.$email_txt;
			$ok = @mail($email_to, $email_subject, $email_message, $headers);
}

function add_stwa($mysql, $contract_id, $y, $m)
{
  global $login;
	mysql_query("BEGIN", $mysql);
	$vat = get_vat($mysql);
  $contract_id = mysql_escape_string($contract_id);
  $y = mysql_escape_string($y);
  $m = mysql_escape_string($m);
	$query = "SELECT
		service.id,
		service.description,
		SUM(charge.value_without_vat) AS charged,
		contract.c_type,
		contract.c_number
		FROM contract
		LEFT JOIN service on service.contract_id = contract.id
		LEFT JOIN charge on charge.service_id = service.id
		WHERE contract.id = '$contract_id'
		AND charge.timestamp >= '$y-$m-01'
		AND charge.timestamp < DATE_ADD('$y-$m-01', INTERVAL 1 MONTH)
		AND `service`.`cash` = 'no'
		GROUP BY service.id
		HAVING service.id IS NOT NULL";
  $list_services = mysql_query($query, $mysql);
	if($list_services == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
    }
	$n = mysql_num_rows($list_services);
	if($n == 0){
    mysql_query("ROLLBACK", $mysql);
		return 0;
		}
	$value_without_vat = 0;
	$service_length = 0;
	for($i = 0; $i < $n; $i++){
		$value_without_vat = round2($value_without_vat + mysql_result($list_services, $i, "charged"));
		if(strlen(mysql_result($list_services, $i, "service.description")) > $service_length){
			$service_length = strlen(mysql_result($list_services, $i, "service.description"));
			}
		}
	$value_with_vat = round2($value_without_vat * (1 + $vat));
	$value_vat = round2($value_with_vat - $value_without_vat);
	// If charge is 0, don't ganerate STWA
	if($value_with_vat == 0){
		mysql_query("ROLLBACK", $mysql);
		return 0;
		}
	$contract_num = mysql_result($list_services, 0, "contract.c_type")."-".mysql_result($list_services, 0, "contract.c_number");
	// Опраделяем номер акта
	$query = "SELECT `value` AS `stwa_num` FROM `counter` WHERE `variable` = 'stwa_num' FOR UPDATE";
  $result = mysql_query($query, $mysql);
  if($result == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
    }
	$stwa_num = 1;
  if(mysql_num_rows($result) == 1){
    $stwa_num = mysql_result($result, 0, "stwa_num");
    }
	else{ // Счетчик номера счета не существует
		$query = "INSERT INTO `counter` ( `variable` , `value` ) VALUES ( 'stwa_num', '1')"; 
		if(mysql_query($query, $mysql) == FALSE){
			$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
		  if(!headers_sent()){
				header ("Location: show_error.php?error=".$msg);
				}
		else{
				echo urldecode($msg);
				}
		  mysql_query("ROLLBACK", $mysql);
		  mysql_close($mysql);
		  exit(1);
		  }
		}
	// Увеличиваем счетчик номеров на 1
	$query = "UPDATE `counter` SET `value` = `value` + 1 WHERE `variable` = 'stwa_num'";
	if(mysql_query($query, $mysql) == FALSE){
		$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
	  if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
	  mysql_query("ROLLBACK", $mysql);
	  mysql_close($mysql);
	  exit(1);
	  }
	$query = "INSERT INTO `stwa`(`stwa_num`, contract_id, create_time, starttime, stoptime, value_without_vat, vat, value_with_vat)
			VALUES(
			'$stwa_num',
			'$contract_id', 
			NOW(), 
			'$y-$m-01', 
			DATE_SUB(DATE_ADD('$y-$m-01', INTERVAL 1 MONTH), INTERVAL 1 DAY), 
			'$value_without_vat', 
			'$value_vat', 
			'$value_with_vat')";
	if(mysql_query($query) == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
    }
	$stwa_id = mysql_insert_id();
	$query = "SELECT
		UNIX_TIMESTAMP(starttime) AS starttime,
		UNIX_TIMESTAMP(stoptime) AS stoptime
		FROM stwa
		WHERE id = '$stwa_id'";
  $result = mysql_query($query, $mysql);
	if($result == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
    }
	$starttime = strftime("%d.%m.%Y", mysql_result($result, 0, "starttime"));
	$stoptime = strftime("%d.%m.%Y", mysql_result($result, 0, "stoptime"));
	$query = "SELECT
		client.id,
		client.full_name,
		client.director,
		client.edrpou,
		client.phone,
		client.tax_number,
		client.licence_number,
		client.phys_zip,
		client.phys_addr_1,
		client.phys_addr_2,
		client.phys_addr_3
		FROM contract
		LEFT join client ON client.id = contract.client_id
		WHERE contract.id = '$contract_id'
		HAVING client.id IS NOT NULL";
  $result = mysql_query($query, $mysql);
	if($result == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
    }
	if(0 == mysql_num_rows($result)){
		$msg = "Не знайдено жодного кл╕╓нта з ╕дентиф╕катором договору '$contract_id'";
		$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
		}
	$client_description = mysql_result($result, 0, "client.full_name");
	$client_director = mysql_result($result, 0, "client.director");
	$client_edrpou = mysql_result($result, 0, "client.edrpou");
	$client_phone = mysql_result($result, 0, "client.phone");
	$client_ipn = mysql_result($result, 0, "client.tax_number");
	$client_licence_number = mysql_result($result, 0, "client.licence_number");
	$phys_zip = mysql_result($result, 0, "client.phys_zip");
	$phys_addr_1 = mysql_result($result, 0, "client.phys_addr_1");
	$phys_addr_2 = mysql_result($result, 0, "client.phys_addr_2");
	$phys_addr_3 = mysql_result($result, 0, "client.phys_addr_3");

	$header = "АКТ #".sprintf("%08u",$stwa_num)."\n";
	$header .= "прийому-передач╕ послуг ╤нтернет\n";
	$header .= "зг╕дно Договору #".$contract_num."\n";
  $header = mysql_escape_string($header);
	
	$body = "\t".$stoptime." м. Ки╖в\n";
	$body .= "ВАТ \"Нац╕ональне Бюро ╤нформац╕╖\" в особ╕ Голови правл╕ння Оксанича ╤.М., ";
	$body .= "в подальшому \"Виконавець\", та ".$client_description." в особ╕ ".$client_director.", ";
	$body .= "в подальшому \"Замовник\", п╕дписали акт про нижченаведене:\n";
	$body .= "1. \"Виконавець\" здав, а \"Замовник\" прийняв послуги мереж╕ ╤нтернет за пер╕од з ".$starttime." по ".$stoptime."\n";
	for($i = 0; $i < $n; $i++){
		$body .= "\t".sprintf("%-".$service_length."s - %.2f\n", mysql_result($list_services, $i, "service.description"), mysql_result($list_services, $i, "charged"));
		}
	$body .= "2. \"Замовник\" п╕дтверджу╓ в╕дпов╕дну як╕сть наданих послуг та в╕дсутн╕сть претенз╕й до \"Виконавця\".\n";
	$body .= "3. Загальна варт╕сть наданих послуг склада╓ ".number_format($value_without_vat, 2, ".", " ")." грн.(".num2str($value_without_vat)."), ";
	$body .= "кр╕м того ПДВ 20% - ".number_format($value_vat, 2, ".", " ")." грн. (".num2str($value_vat).").\n";
	$body .= "\tРазом ".number_format($value_with_vat, 2, ".", " ")." грн. (".num2str($value_with_vat).")\n";
	$body .= "4. Укладений акт ╓ основою для к╕нцевих вза╓мних розрахунк╕в.\n";
	$body = mysql_escape_string($body);
	
	$footer1 = "Виконавець:\n";
	$footer1 .= "_________________/Оксанич ╤.М./\n";
	$footer1 .= "ВАТ \"Нац╕ональне Бюро ╤нформац╕╖\"\n";
	$footer1 .= "╢ДРПОУ 30112808\n";
	$footer1 .= "Р/р 260083161601 в ПАТ \"Аграрний комерц╕йний банк\" МФО 322302\n";
	$footer1 .= "Адреса: 03150, Ки╖в, вул. Велика Васильк╕вська, 106, оф. 2";
	$footer1 = mysql_escape_string($footer1);

	$footer2 = "Замовник:\n";
	$footer2 .= "_________________/".$client_director."/\n";
	$footer2 .= $client_description."\n";
	$footer2 .= "╢ДРПОУ ".$client_edrpou." тел. ".$client_phone."\n";
	$footer2 .= "╤ПН ".$client_ipn.", номер св╕доцтва ".$client_licence_number."\n";
	$footer2 .= "Адреса: ".$phys_zip.", ".$phys_addr_1.", ".$phys_addr_2.", ".$phys_addr_3."\n";
	$footer2 = mysql_escape_string($footer2);
	
	$query = "UPDATE `stwa` SET
			header = '$header',
			body = '$body',
			footer1 = '$footer1',
			footer2 = '$footer2'
			WHERE id = '$stwa_id'";
	if(mysql_query($query) == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
			}
		else{
			echo urldecode($msg);
			}
    mysql_query("ROLLBACK", $mysql);
    mysql_close($mysql);
    exit(1);
    }
	
	mysql_query("COMMIT", $mysql);
	return $stwa_id;
}

function delete_stwa($mysql, $stwa)
{
	if(is_array($stwa)){
		$stwa_id = $stwa[0];
		$ids = array_values($stwa);
		}
	else{
		$ids = array($stwa);
		}
	mysql_query("BEGIN", $mysql);
	foreach($ids as $stwa_id){
		$stwa_id = mysql_escape_string($stwa_id);
		$query = "DELETE FROM stwa WHERE id = '$stwa_id'";
		if(mysql_query($query, $mysql) == FALSE){
		  $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
		  $msg = str_replace("\n", "<br>", $msg);
		  $msg = urlencode($msg);
		  header ("Location: show_error.php?error=".$msg);
		  mysql_query("ROLLBACK", $mysql);
		  mysql_close($mysql);
		  exit(1);
		  }
		}
  mysql_query("COMMIT", $mysql);
}

function print_stwa($mysql, $stwa, $num_copies, $save_name, $inline, $send_pdf)
{
	global $TMPDIR;
	if(is_array($stwa)){
		$stwa_id = $stwa[0];
		$ids = array_values($stwa);	
		}
	else{
		$ids = array($stwa);
		}
	$pdf=new PDF("P", "mm", "A4");
	$pdf->AddFont("cour","","cour.php");
	$pdf->AddFont("arial","","arial.php");
	$pdf->AddFont("courbd","B","courbd.php");

	$pdf->Open();
	$pdf->SetAutoPageBreak("FALSE"); 
	mysql_query("BEGIN");
	foreach($ids as $stwa_id){
		$stwa_id = mysql_escape_string($stwa_id);
		$query = "SELECT * FROM stwa WHERE id = '$stwa_id'";
		//echo $query;
		//exit(0);
		$result = mysql_query($query, $mysql);
		if($result == FALSE){
			$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK", $mysql);
			mysql_close($mysql);
			exit(1);
			}
		if(mysql_num_rows($result) == 1){
			$stwa_num = mysql_result($result, 0, "stwa_num");
			$header = mysql_result($result, 0, "header");
			$body = mysql_result($result, 0, "body");
			$footer1 = mysql_result($result, 0, "footer1");
			$footer2 = mysql_result($result, 0, "footer2");
			}
		else{
			$msg = "Нев╕домий ╕дентиф╕катор акт╕ виконаних роб╕т '$stwa_id'";
			$msg = str_replace("\n", "<br>", $msg);
			$msg = urlencode($msg);
			header ("Location: show_error.php?error=".$msg);
			mysql_query("ROLLBACK", $mysql);
			mysql_close($mysql);
			exit(1);
			}
		$pdf->AddPage();
		$pdf->SetLeftMargin(20);
		for($u = 0; $u < $num_copies; $u++){
			if($pdf->GetY() > 158){
				$pdf->AddPage();
				}
			$pdf->SetFont("courbd", "B", 10);
			//$pdf->SetX(0);
			$pdf->MultiCell(0, 3, $header, 0, "C");
			$pdf->Ln();
			$pdf->Ln();
			$pdf->SetFont("courbd", "B", 8);
			$pdf->MultiCell(0, 4, $body, 0, "L");
			$pdf->Ln();
			$x = $pdf->GetX();
			$y = $pdf->GetY();
			$pdf->SetFont("arial", "", 9);
			$pdf->MultiCell(80, 4, $footer1, 0, "L");
			$pdf->SetXY($x + 90, $y);
			$pdf->MultiCell(0, 4, $footer2, 0, "L");
			$pdf->Ln();
			$pdf->Ln();
			$y = ($pdf->GetY() < 148) ? 148 : $pdf->GetY();
			if(($u + 1) % 2 == 1){
				$pdf->Line(0, $y, 210, $y);
				}
			$pdf->Ln();
			$pdf->SetY($y + 10);
			}
		}
	mysql_query("COMMIT");
	//header("Content-Type: application/pdf");
	//header("Content-Length: ".strlen($pdf_body));
	$file = tempnam($TMPDIR, "netstore.".session_id());
	$pdf->Output($file, false);
	//$pdf->Output();
	chmod($file, 0644);
	if($send_pdf){
		if($save_name == ""){
			$save_name = "statement_of_work_agreement.pdf";
			}
		if($inline != "inline"){
			header("Content-disposition: attachment; filename=".$save_name);
			}
		else{
			header("Content-disposition: inline; filename=".$save_name);
			}
		header("Content-Type: application/pdf");
		header("Content-Length: ".filesize($file));
		readfile($file);
		unlink($file);
		}
	return $file;
}

function genpasswd($n)
{
	$password = "";
	while(TRUE){
		$c = rand(32, 255);
		if(ctype_alnum($c)){
			$password .= sprintf("%c", $c);
			}
		if(strlen($password) >= $n){
			break;	
			}
		}
	return $password;
}

function log_event($mysql, $client_id, $table, $tkey, $action_type, $details)
{
  global $login, $db;

	$client_id = mysql_escape_string($client_id);
	$table = mysql_escape_string($table);
	$tkey = mysql_escape_string($tkey);
	$action_type = mysql_escape_string($action_type);
	$details = mysql_escape_string($details);
	
	if(FALSE == mysql_select_db($db)){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
    	exit(1);
			}
		else{
			echo urldecode($msg);
    	exit(1);
			}
		}
												
	$query = "INSERT INTO `log_event` (`session_id`, `timestamp`, `script`, `operator`, `client_id`, `table`, `tkey`, `action_type`, `details`)
			VALUES('".session_id()."', NOW(), '".$_SERVER["PHP_SELF"]."', '$login', '$client_id', '$table', '$tkey', '$action_type', '$details')";

	if(mysql_query($query, $mysql) == FALSE){
  	$msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
  	$msg = str_replace("\n", "<br>", $msg);
		$msg = urlencode($msg);
    if(!headers_sent()){
			header ("Location: show_error.php?error=".$msg);
    	exit(1);
			}
		else{
			echo urldecode($msg);
    	exit(1);
			}
    }
}
?>
