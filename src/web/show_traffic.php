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
    && $_SESSION['authdata']['permlevel'] != 'client'
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
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
	$REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
  if($REQUEST_METHOD == "GET"){
		$client_id = mysql_escape_string($_GET["client_id"]);
		$dbegin = mysql_escape_string($_GET["dbegin"]);
		$dend = mysql_escape_string($_GET["dend"]);
		if(isset($_GET["factor"])){
			$factor = mysql_escape_string($_GET["factor"]);
			}
		else{
			$factor = 1048576;
			}
		}
  if($REQUEST_METHOD == "POST"){
		$client_id = mysql_escape_string($_POST["client_id"]);
		$dbegin = mysql_escape_string($_POST["dbegin"]);
		$dend = mysql_escape_string($_POST["dend"]);
		if(isset($_POST["factor"])){
			$factor = mysql_escape_string($_POST["factor"]);
			}
		else{
			$factor = 1048576;
			}
		}

  $query = "select client.id,
    client.description,
    client.manager_id,
    client.person,
    client.phone,
    client.email
    from client
    where client.id = '$client_id'";
  $result = mysql_query($query);
  if($result == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  if($permlevel == 'manager'){
    $readonlystatus = "readonly";
    if($login != mysql_result($result, 0, "client.manager_id")){
      header($start_url);
      }
    }
?>
  <tr>
		<td valign="top" width="20%"><? include("left_cl.php"); ?></td>
    <td width="75%" valign="top">
      <table border="0" width="100%">
        <tr>
          <td>
            <table border="0" width="100%">
<?
  $qry_last_update = "select max(timestamp) as lastupdate from feeding";
  $res_last_update = mysql_query($qry_last_update);
  if($res_last_update == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $n_last_update = mysql_num_rows($res_last_update);
  if($n_last_update == 1){
    $last_update = "(Останн╓ поновлення: ".mysql_result($res_last_update, 0, "lastupdate").")";
    }
  // Whole traffic
  $qry_whole = "SELECT 
		SUM(traffic_snapshot.incoming) AS incoming,
    SUM(traffic_snapshot.outcoming) AS outcoming
    FROM traffic_snapshot
    WHERE traffic_snapshot.client_id = $client_id
		AND UNIX_TIMESTAMP(traffic_snapshot.timestamp) >= $dbegin
		AND UNIX_TIMESTAMP(traffic_snapshot.timestamp) < $dend";
	//echo $qry_whole;
	$res_whole = mysql_query($qry_whole);
  if($res_whole == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
      
  $n_whole = mysql_num_rows($res_whole);
  if($n_whole != 0){
    $full_incoming = mysql_result($res_whole, 0, "incoming");
    $full_outcoming = mysql_result($res_whole, 0, "outcoming");
    }
  else{
    $full_incoming = 0;
    $full_outcoming = 0;
    }
      
  if($factor == 1 || $factor == 1024) $prec = 0;
  if($factor == 1048576 || $factor == 1073741824) $prec = 4;
      
?>
              <tr bgcolor="white">
                <td>
                  <form method="POST" action="<? echo $_SERVER['PHP_SELF']; ?>">
                  <table width="100%">
										<tr>
											<th align="right">Пер╕од:</th>
											<td colspan="2"><?echo strftime("%e %B %Y %X (%Z)",$dbegin)?> - <?echo strftime("%e %B %Y %X (%Z)",$dend - 1)?></td>
										</tr>
                    <tr>
                      <td><? echo $last_update?></td>
                      <td align="right">Показати в</td>
                      <td>
                        <select name="factor" onchange="this.form.submit()">
                          <option value="1073741824" <? if($factor == 1073741824) echo "selected"?> >Г╕габайтах</option>
                          <option value="1048576" <? if($factor == 1048576) echo "selected"?>>Мегабайтах</option>
                          <option value="1024" <? if($factor == 1024) echo "selected"?>>К╕лобайтах</option>
                          <option value="1" <? if($factor == 1) echo "selected"?>>Байтах</option>
                        </select>
                        <input type="hidden" name="client_id" value="<? echo $client_id ?>">
                        <input type="hidden" name="dbegin" value="<? echo $dbegin ?>">
                        <input type="hidden" name="dend" value="<? echo $dend ?>">
                      </td>
                    </tr>
                  </table>
                  </form>
                </td>
              </tr>

              <tr bgcolor="white">
                <td>
                  <table border="0" width="100%" cellspacing="1" cellpadding="2" bgcolor="silver">
                    <tr bgcolor="#D3D3D3">
                      <th colspan="3">Загальний траф╕к</th>
                    </tr>
                    <tr bgcolor="#E4E4E4">
                      <td align="right" width="34%"><i>Сумарний</i></td>
                      <td align="right" width="33%"><i>Вх╕дний</i></td>
                      <td align="right" width="33%"><i>Вих╕дний</i></td>
                    </tr>
                    <tr bgcolor="white">
                      <td bgcolor="#ffffcc" align="right"><b><?echo number_format(($full_incoming + $full_outcoming)/$factor, $prec, ".", " ")?></b></td>
                      <td align="right"><?echo number_format($full_incoming/$factor, $prec, ".", " ")?></td>
                      <td align="right"><?echo number_format($full_outcoming/$factor, $prec, ".", " ")?></td>
                    </tr>
                  </table>
                </td>
              </tr>
<?
  $query = "SELECT 
		filter.id,
		filter.client_id,
    filter.description,
    SUM(filter_counter_snapshot.incoming) as incoming,
    SUM(filter_counter_snapshot.outcoming) as outcoming
    from filter
    left join filter_counter_snapshot on filter.id = filter_counter_snapshot.filter_id
    WHERE filter.client_id = $client_id
		AND UNIX_TIMESTAMP(filter_counter_snapshot.timestamp) >= $dbegin
		AND UNIX_TIMESTAMP(filter_counter_snapshot.timestamp) < $dend
		GROUP BY filter.id";
  //  echo $query;
  $res = mysql_query($query);
  if($res == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    mysql_close($mysql);
    exit(1);
    }
  $n_filter = mysql_num_rows($res);
  if($n_filter != 0){
?>
              <tr bgcolor="white">
                <td>
                  <table border="0" width="100%" cellspacing="1" cellpadding="2" bgcolor="silver">
<?
    for($i = 0; $i < $n_filter; $i++){
      $description = mysql_result($res, $i, "filter.description");
      $incoming = mysql_result($res, $i, "incoming");
      $outcoming = mysql_result($res, $i, "outcoming");
?>
                    <tr bgcolor="#D3D3D3">
                      <th colspan="3"><? echo $description?></th>
                    </tr>
                    <tr bgcolor="#E4E4E4">
                      <td align="right" width="34%"><i>Сумарний</i></td>
                      <td align="right" width="33%"><i>Вх╕дний</i></td>
                      <td align="right" width="33%"><i>Вих╕дний</i></td>
                    </tr>
                    <tr bgcolor="white">
                      <td align="right"><b><? echo number_format(($incoming + $outcoming)/$factor, $prec, ".", " ")?></b></td>
                      <td align="right"><? echo number_format($incoming/$factor, $prec, ".", " ")?></td>
                      <td align="right"><? echo number_format($outcoming/$factor, $prec, ".", " ")?></td>
                    </tr>
<?
      }
?>
                  </table>
                </td>
              </tr>
<?
    }
?>
              <tr bgcolor="white">
                <td align="center">
                  <table cellspacing="1" cellpadding="2" border="0" bgcolor="silver">
                  <caption>Арх╕в зв╕т╕в по м╕сяцям</caption>
<?
  $m = date("m");
  for($i = 0; $i < 4 ; $i++){
    echo "
                    <tr bgcolor=\"white\">";
    for($j = 0; $j < 3 ; $j++){
      $month = strftime("%b/%Y",mktime(0,0,0,$m,1,date("Y")));
      $dbegin = mktime(0, 0, 0, $m, 1, date("Y"));
      $dend = mktime(0, 0, 0, $m + 1 , 1, date("Y"));
			if($dend > mktime()){
				$dend = mktime();
				}
      echo "
                      <td><a href=\"show_traffic.php?dbegin=$dbegin&amp;dend=$dend&amp;client_id=$client_id&amp;factor=$factor&amp;cl_login=$login\">$month</a></td>";
      $m--;
      }
    echo "
                    </tr>";
    }
  echo "
                  </table>\n";
?>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td colspan="2" align="right"><a href="http://validator.w3.org/"><img border="0" src="img/valid-html401.png" alt="Valid HTML 4.01!" height="31" width="88"></a></td>
  </tr>
<?
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
