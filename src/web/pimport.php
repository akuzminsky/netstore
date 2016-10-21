<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  $REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
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
    session_timeout();
    }
  if(FALSE == mysql_select_db($db)){
    $msg = "Error: ".mysql_error()." while connecting to database ".$db;
    $msg = str_replace("\n", "<br>", $msg);
    $msg = urlencode($msg);
    header ("Location: show_error.php?error=".$msg);
    mysql_close($mysql);
    exit(1);
    }
  if($REQUEST_METHOD == "POST"){
    if(!empty($_POST["upload"])){
      if(is_uploaded_file($_FILES['userfile']['tmp_name'])){
        $fdest = "$TMPDIR/".$_FILES['userfile']['name'];
        if(!move_uploaded_file($_FILES['userfile']['tmp_name'], $fdest)){
          $msg = "Не вдалося завантажити файл банк╕всько╖ виписки";  
          $msg = str_replace("\n", "<br>", $msg);
          $msg = urlencode($msg);
          header ("Location: show_error.php?error=".$msg);
          mysql_close($mysql);
          exit(1);
          }
        mysql_query("BEGIN");
        $query = "DELETE FROM `extract` WHERE session_id = '".session_id()."'";
        if(mysql_query($query) == FALSE){
          $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
          $msg = str_replace("\n", "<br>", $msg);
          $msg = urlencode($msg);
          header ("Location: show_error.php?error=".mysql_error());
          mysql_query("ROLLBACK");
          mysql_close($mysql);
          exit(1);                    
          }
        $f = fopen($fdest, "r");
        if($f == FALSE){
          $msg = "Не вдалося в╕дкрити файл $fdest";
          $msg = str_replace("\n", "<br>", $msg);
          $msg = urlencode($msg);
          header ("Location: show_error.php?error=".mysql_error());
          mysql_query("ROLLBACK");
          mysql_close($mysql);
          exit(1);                    
          }
        while(!feof($f)){
          $buffer = fgets($f);
					$payer_okpo = str_replace(" ", "", substr($buffer, 391, 14));
          // We need olny debit documents
          if(substr($buffer, 46, 1) == "1" && $payer_okpo != "30112808"){
            $query = "INSERT INTO `extract` VALUES
                ('".mysql_escape_string(substr($buffer, 0, 9))."',
                '".mysql_escape_string(substr($buffer, 9, 14))."',
                '".mysql_escape_string(substr($buffer, 23, 9))."',
                '".mysql_escape_string(substr($buffer, 32, 14))."',
                '".mysql_escape_string(substr($buffer, 46, 1))."',
                '".mysql_escape_string(round2(substr($buffer, 47, 16) / 100))."',
                '".mysql_escape_string(substr($buffer, 63, 2))."',
                '".mysql_escape_string(convert_cyr_string(substr($buffer, 65, 10), "w", "k"))."',
                '".mysql_escape_string(substr($buffer, 75, 3))."',
                '".mysql_escape_string(substr($buffer, 78, 6))."',
                '".mysql_escape_string(substr($buffer, 84, 6))."',
                '".mysql_escape_string(convert_cyr_string(substr($buffer, 90, 38), "w", "k"))."',
                '".mysql_escape_string(convert_cyr_string(substr($buffer, 128, 38), "w", "k"))."',
                '".mysql_escape_string(convert_cyr_string(substr($buffer, 166, 160), "w", "k"))."',
                '".mysql_escape_string(convert_cyr_string(substr($buffer, 326, 60), "w", "k"))."',
                '".mysql_escape_string(convert_cyr_string(substr($buffer, 386, 3), "w", "k"))."',
                '".mysql_escape_string(substr($buffer, 391, 14))."',
                '".mysql_escape_string(substr($buffer, 405, 14))."',
                '".mysql_escape_string(substr($buffer, 419, 9))."',
                '".session_id()."')";
            if(mysql_query($query) == FALSE){
              $msg = "Error: ".mysql_error($mysql)." while executing:\n".$query;
              $msg = str_replace("\n", "<br>", $msg);
              $msg = urlencode($msg);
              header ("Location: show_error.php?error=".$msg);
              mysql_query("ROLLBACK");
              mysql_close($mysql);
              exit(1);                    
              }
            }
          }
        mysql_query("COMMIT");
        unlink($fdest);
        }
      }
    if(!empty($_POST["save"])){
      // save payment
			//echo "<pre>\n";
			//print_r($_POST);
			//echo "</pre>\n";
			mysql_query("BEGIN");
			$vat = get_vat($mysql);
			if(isset($_POST["document_id"])){
				foreach(array_values($_POST["document_id"]) as $document_id){
					$document_id = mysql_escape_string($document_id);
					$countract_id = mysql_escape_string($_POST["doc_id".$document_id."_contract_id"]);
					// Get payment document info
					$q = "SELECT * FROM `extract` WHERE session_id ='".session_id()."' AND document_id = '$document_id'";
					$r = mysql_query($q);
		      if($r == FALSE){
		        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
		        $msg = str_replace("\n", "<br>", $msg);
		        $msg = urlencode($msg);
		        header ("Location: show_error.php?error=".$msg);
		        mysql_query("ROLLBACK");
		        mysql_close($mysql);
		        exit(1);                    
		        }
					$n = mysql_num_rows($r);
					if($n == 0){
						$msg = "В виписц╕ в╕дсутн╕й документ '$document_id'";
		        $msg = str_replace("\n", "<br>", $msg);
		        $msg = urlencode($msg);
		        header ("Location: show_error.php?error=".$msg);
		        mysql_query("ROLLBACK");
		        mysql_close($mysql);
		        exit(1);                    
						}
					if($n > 1){
						$msg = "В таблиц╕ `extract' б╕льше одного документа '$document_id'";
		        $msg = str_replace("\n", "<br>", $msg);
		        $msg = urlencode($msg);
		        header ("Location: show_error.php?error=".$msg);
		        mysql_query("ROLLBACK");
		        mysql_close($mysql);
		        exit(1);                    
						}
					$timestamp = mysql_result($r, 0, "document_date");
					$value_without_vat = round2(mysql_result($r, 0, "sum") / (1 + $vat));
					$notice = mysql_escape_string(mysql_result($r, 0, "payment_purpose"));
					// Add payment
					$q = "INSERT INTO payment(contract_id, timestamp, value_without_vat, cash, operator, notice)
							VALUES('$countract_id', '$timestamp', '$value_without_vat', 'no', '$login', '$notice')";
		      if(mysql_query($q) == FALSE){
		        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
		        $msg = str_replace("\n", "<br>", $msg);
		        $msg = urlencode($msg);
		        header ("Location: show_error.php?error=".$msg);
		        mysql_query("ROLLBACK");
		        mysql_close($mysql);
		        exit(1);                    
		        }
					// Delete document from table `extract'
					$q = "DELETE FROM `extract` WHERE session_id = '".session_id()."' AND document_id = '$document_id'";
		      if(mysql_query($q) == FALSE){
		        $msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
		        $msg = str_replace("\n", "<br>", $msg);
		        $msg = urlencode($msg);
		        header ("Location: show_error.php?error=".$msg);
		        mysql_query("ROLLBACK");
		        mysql_close($mysql);
		        exit(1);                    
		        }
					}
				}
			mysql_query("COMMIT");
			}
    // Output form with list of payments
    $query = "SELECT * from `extract` WHERE session_id = '".session_id()."'";
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
?>
  <tr>
    <td valign="top" width="20%"><? include("left_bk.php");?></td>
    <td width="80%">
      <form method="POST" action="<? echo $_SERVER["PHP_SELF"];?>">
      <table cellspacing="1" cellpadding="2" bgcolor="silver">
      <caption>Розп╕знан╕ оплати</caption>
<?
      for($i = 0; $i < $n; $i++){
        $document_number = mysql_result($result, $i, "document_number"); 
        $document_id = mysql_result($result, $i, "document_id");
        $document_date = mysql_result($result, $i, "document_date");
        $sum = mysql_result($result, $i, "sum");
        $payer_account = mysql_result($result, $i, "payer_account");
        $payer_description = mysql_result($result, $i, "payer_description");
        $payer_okpo = mysql_result($result, $i, "payer_okpo");
        $payer_okpo = str_replace(" ", "", $payer_okpo);
        $payer_mfo_bank = mysql_result($result, $i, "payer_mfo_bank");
        $payment_purpose = mysql_result($result, $i, "payment_purpose");
        // Get list of contracts of client
        $q = "SELECT
            contract.id,
            contract.c_type,
            contract.c_number,
            contract.description,
            contract.start_time
            FROM client
            LEFT JOIN contract ON client.id = contract.client_id
            WHERE client.edrpou = '$payer_okpo'
						AND contract.expire_time = '0000-00-00'
						HAVING contract.id IS NOT NULL";
        $r = mysql_query($q);
        if($r == FALSE){
          $msg = "Error: ".mysql_error($mysql)." while executing:\n".$q;
          $msg = str_replace("\n", "<br>", $msg);
          $msg = urlencode($msg);
          header ("Location: show_error.php?error=".$msg);
          mysql_query("ROLLBACK");
          mysql_close($mysql);
          exit(1);                    
          }
        $n_contracts = mysql_num_rows($r);
        if($n_contracts > 1){
          $parse_color_code = "yellow";
          }
        if($n_contracts == 0){
          $parse_color_code = "red";
          }
        if($n_contracts == 1){
          $parse_color_code = "green";
          }
?>
        <tr bgcolor="white">
          <td>
            <table cellspacing="1" cellpadding="2" bgcolor="silver" width="100%">
              <tr bgcolor="<? echo $parse_color_code; ?>">
                <td>
                  <table cellspacing="1" cellpadding="2" bgcolor="silver" width="100%">
                    <tr bgcolor="white">
                      <td>
                        <table cellspacing="1" cellpadding="2" bgcolor="silver" width="100%">
                          <tr bgcolor="white">
                            <td>Документ # <? echo $document_number; ?></td>
                            <td>Дата <? echo $document_date; ?></td>
                            <td colspan="2">Сума <b><? echo $sum; ?></b></td>
                          </tr>
                          <tr bgcolor="white">
                            <td>Платник</td>
                            <td><? echo $payer_account; ?></td>
                            <td><? echo $payer_description; ?></td>
                            <td>ОКПО <? echo $payer_okpo; ?></td>
                          </tr>
                          <tr bgcolor="white">
                            <td>Банк платника</td>
                            <td colspan="3"><? echo $payer_mfo_bank; ?></td>
                          </tr>
                          <tr bgcolor="white">
                            <td>Призначення платежу</td>
                            <td colspan="3"><? echo $payment_purpose; ?></td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    <tr bgcolor="white">
                      <td>
                        <table cellspacing="1" cellpadding="2" bgcolor="silver" width="100%">
                        <caption><small><?if($n_contracts == 0) echo "<font color=\"red\">неоп╕знана оплата</font>"; else echo "Договори кл╕╓нта"; ?></small></caption>
<?
  for($k = 0; $k < $n_contracts; $k++){
    $contract_id = mysql_result($r, $k, "contract.id");
    $c_type = mysql_result($r, $k, "contract.c_type");
    $c_number = mysql_result($r, $k, "contract.c_number");
    $description = mysql_result($r, $k, "contract.description");
    $start_time = mysql_result($r, $k, "contract.start_time");
?>
                          <tr bgcolor="white">
                            <td><input type="radio" name="doc_id<? echo $document_id; ?>_contract_id" value="<? echo $contract_id; ?>" <? if($k ==0) echo "checked"; ?>></td>
                            <td><? echo $c_type."-".$c_number; ?></td>
                            <td><? echo $start_time; ?></td>
                            <td><? echo $description; ?></td>
                          </tr>
<?
    }
?>
                        </table>
                      </td>
                    </tr>
                  </table>
                </td>
<?
        if($n_contracts != 0){
?>
                <td><input type="checkbox" name="document_id[]" value="<? echo $document_id; ?>"></td>
<?
          }
        else{
            echo "<td>&nbsp</td>\n";
          }
?>
              </tr>
            </table>
          </td>
        </tr>
<?
        }
?>
        <tr bgcolor="white">
          <td colspan="2" align="right"><input type="submit" name="save" value="Зберегти"></td>
        </tr>
      </table>
      </form>
    </td>
  </tr>
<?
    }
  if($REQUEST_METHOD == "GET"){
?>
  <tr>
    <td valign="top" width="20%"><? include("left_bk.php");?></td>
    <td width="80%">
      <form method="POST" action="<? echo $_SERVER["PHP_SELF"];?>" enctype="multipart/form-data">
      <table cellspacing="1" cellpadding="2" bgcolor="silver">
        <tr bgcolor="white">
          <td>Файл банк╕всько╖ виписки</td>
          <td><input type="hidden" name="MAX_FILE_SIZE" value="1000000"><input name="userfile" type="file"></td>
        </tr>
        <tr bgcolor="white">
          <td colspan="2" align="right"><input type="submit" name="upload" value="Завантажити"></td>
        </tr>
      </table>
      </form>
    </td>
  </tr>
<?
    readfile("$DOCUMENT_ROOT/bottom.html");
    }
?>
