<?
 $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
 readfile("$DOCUMENT_ROOT/head.html");
 include("$DOCUMENT_ROOT/netstorecore.php");
 session_start();
 if(!@session_is_registered('authdata')){
 	header($start_url);
	}
 if($_SESSION['authdata']['permlevel'] != 'admin' && $_SESSION['authdata']['permlevel'] != 'manager' && $_SESSION['authdata']['permlevel'] != 'client' && $_SESSION['authdata']['permlevel'] != 'topmanager'){
 	header($start_url);
	}
 
 $authdata = $_SESSION['authdata'];
 include("top.php");
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
 if(isset($_POST[client_id])){
 	$client_id = $_POST[client_id];
	}
 else{
 	$query = "select id from client where login = '$login'";
	$result = mysql_query($query);
	if($result == FALSE){
		header ("Location: show_error.php?error=".mysql_error());
		mysql_close($mysql);
		exit(1);
		}
	$client_id = mysql_result($result, 0, "id");
	$_POST[client_id] = $client_id;
	}
 // If user has "client" privileges, check his validity
 if($_SESSION['authdata']['permlevel'] == 'client'){
 	if(!check_client_validity($client_id, $mysql)){
		header($start_url);
		}
 	}

 if($_SESSION['authdata']['permlevel'] == 'client'){
 ?>
 <tr>
 	<td valign=top align=right><a href=logout.php>Выйти</a></td>
 </tr>
 <?
 	}
 ?>
 <tr>
 	<td>
 		<table border=0 width=100%>
<?	$report_type = $_POST[report_type];
	switch($report_type){
		case "daily": ?>
			<tr>
				<td>
				<p>Для заказа детальной статистики укажите период, по которому будет формироваться отчет. 
				Для получения отчета по электронной почте, отметьте галочкой соответствующую опцию и укажите адрес.
				<p>
				Если выбрать опцию <b><i>Опубликовать на сервере статистики</i></b>, отчет позже будет доступен на 
				сервере статистики в разделе "Отчеты".
				<p>Когда генерация отчета будет закончена, уведомление об этом может быть выслано Вам по электронной почте
				либо на мобильный телефон.
				<p>
				</td>
			</tr>
			<tr>
				<td>
					<table width=100% cellspacing="1" cellpadding="2" border="0" bgcolor=silver>
					<form action="submit_order.php" method=post>
						<tr bgcolor=white>
							<th align=center colspan=2>Отчет: использование трафика по дням</th>
						</tr>
						<tr bgcolor=white>
							<td align=left>Период</td>
							<td><?form_period("period");?></td>
						</tr>
						<tr bgcolor=white>
							<td align=left>Готовый отчет</td>
							<td>
								<table>
								<tr>
								<td><input type="checkbox" name="result2email" id="dailyreport_result2email_id"></td>
								<td><label for="dailyreport_result2email_id">Выслать по почте</label></td>
								<td><input type=text name="resultemail"></td>
								</tr>
								<tr>
								<td><input type="checkbox" name="post2site" id="dailyreport_post2site_id"></td>
								<td colspan=2><label for="dailyreport_post2site_id">Опубликовать на сервере статистики</label></td>
								</tr>
								</table>
							</td>
						</tr>
						<tr bgcolor=white>
							<td align=left>После завершения формирования отчета</td>
							<td>
								<table>
								<tr>
								<td><input type="checkbox" name="notify2email" id="dailyreport_notify2email_id"></td>
								<td colspan=2><label for="dailyreport_notify2email_id">Выслать уведомление по почте</label></td>
								<td><input type=text name="notifyemail"></td>
								</tr>
								<tr>
								<td><input type="checkbox" name="notify2sms" id="dailyreport_notify2sms_id"></td>
								<td colspan=2><label for="dailyreport_notify2sms_id">Послать уведомление как SMS на телефон</label></td>
								<td><select name="operator">
									<option value="+38067">+38 067 (Киевстар)
									<option value="+38050">+38 050 (UMC)
								</td>
								<td><input type=text name="notifycell"></td>
								</tr>
								</table>
							</td>	
						</tr>
						<tr bgcolor=white><th colspan=2><input type=submit value="Готово"></th>
						</tr>
					<input type="hidden" name="report_type" value="<?echo $report_type?>">
					<input type="hidden" name="client_id" value="<?echo $client_id?>">
					<input type="hidden" name="cl_login" value="<?echo $login?>">
					</form>
					</table>
				</td>
			</tr>
			<?
			break;
		case "hourly":
			?>
			<tr>
				<td>
				<p>Для заказа детальной статистики укажите период, по которому будет формироваться отчет. 
				Для получения отчета по электронной почте, отметьте галочкой соответствующую опцию и укажите адрес.
				<p>
				Если выбрать опцию <b><i>Опубликовать на сервере статистики</i></b>, отчет позже будет доступен на 
				сервере статистики в разделе "Отчеты".
				<p>Когда генерация отчета будет закончена, уведомление об этом может быть выслано Вам по электронной почте
				либо на мобильный телефон.
				<p>
				</td>
			</tr>
			<tr>
				<td>
					<table width=100% cellspacing="1" cellpadding="2" border="0" bgcolor=silver>
					<form action="submit_order.php" method=post>
						<tr bgcolor=white>
							<th align=center colspan=2>Отчет: использование трафика по часам</th>
						</tr>
						<tr bgcolor=white>
							<td align=left>Период</td>
							<td><?form_period("period");?></td>
						</tr>
						<tr bgcolor=white>
							<td align=left>Готовый отчет</td>
							<td>
								<table>
								<tr>
								<td><input type="checkbox" name="result2email" id="hourlyreport_result2email_id"></td>
								<td><label for="hourlyreport_result2email_id">Выслать по почте</label></td>
								<td><input type=text name="resultemail"></td>
								</tr>
								<tr>
								<td><input type="checkbox" name="post2site" id="hourlyreport_post2site_id"></td>
								<td colspan=2><label for="hourlyreport_post2site_id">Опубликовать на сервере статистики</label></td>
								</tr>
								</table>
							</td>
						</tr>
						<tr bgcolor=white>
							<td align=left>После завершения формирования отчета</td>
							<td>
								<table>
								<tr>
								<td><input type="checkbox" name="notify2email" id="hourlyreport_notify2email_id"></td>
								<td colspan=2><label for="hourlyreport_notify2email_id">Выслать уведомление по почте</label></td>
								<td><input type=text name="notifyemail"></td>
								</tr>
								<tr>
								<td><input type="checkbox" name="notify2sms" id="hourlyreport_notify2sms_id"></td>
								<td colspan=2><label for="hourlyreport_notify2sms_id">Послать уведомление как SMS на телефон</label></td>
								<td><select name="operator">
									<option value="+38067">+38 067 (Киевстар)
									<option value="+38050">+38 050 (UMC)
								</td>
								<td><input type=text name="notifycell"></td>
								</tr>
								</table>
							</td>	
						</tr>
						<tr bgcolor=white><th colspan=2><input type=submit value="Готово"></th>
						</tr>
					<input type="hidden" name="report_type" value="<?echo $report_type?>">
					<input type="hidden" name="client_id" value="<?echo $client_id?>">
					<input type="hidden" name="cl_login" value="<?echo $login?>">
					</form>
					</table>
				</td>
			</tr>
			<?
			break;
		case "flows":
			?>
			<tr>
				<td>
				<p><b>ВНИМАНИЕ!</b><br>Информация о трафике в этом отчете очень детализирована и размер самого 
				отчета сопоставим с величиной трафика. Поэтому мы рекомендуем заказывать такой отчет в случае
				крайней необходимости и по возможности за небольшие периоды времени.
				<p>Для заказа детальной статистики укажите период, по которому будет формироваться отчет. 
				Для получения отчета по электронной почте, отметьте галочкой соответствующую опцию и укажите адрес.
				<p>
				Обращаем Ваше внимание на то, что этот отчет не может быть опубликован на сервере статистики, его
				можно получить только по электронной почте.
				<p>Когда генерация отчета будет закончена, уведомление об этом может быть выслано Вам по электронной почте
				либо на мобильный телефон.
				<p>
				</td>
			</tr>
			<tr>
				<td>
					<table width=100% cellspacing="1" cellpadding="2" border="0" bgcolor=silver>
					<form action="submit_order.php" method=post>
						<tr bgcolor=white>
							<th align=center colspan=2>Отчет: использование трафика по потокам</th>
						</tr>
						<tr bgcolor=white>
							<td align=left>Период</td>
							<td><?form_period("period");?></td>
						</tr>
						<tr bgcolor=white>
							<td align=left>Готовый отчет</td>
							<td>
								<table>
								<tr>
								<td><input type="checkbox" name="result2email" id="flowsreport_result2email_id"></td>
								<td><label for="flowsreport_result2email_id">Выслать по почте</label></td>
								<td><input type=text name="resultemail"></td>
								</tr>
								<tr>
								<td colspan=2>Представить в виде архива</td>
								<td><select name=archtype>
									<option value="zip" selected>.zip
									<option value="rar">.rar
									<option value="gz">.gz
								</td>
								<td><!--<input type="checkbox" name="resolve_ip" id="flowsreport_resolve_ip_id">--></td>
								<td><!--<label for="flowsreport_resolve_ip_id">В отчете показывать доменные имена вместо IP-адресов</label>--></td>
								</tr>
								</table>
							</td>
						</tr>
						<tr bgcolor=white>
							<td align=left>После завершения формирования отчета</td>
							<td>
								<table>
								<tr>
								<td><input type="checkbox" name="notify2email" id="flowsreport_notify2email_id"></td>
								<td colspan=2><label for="flowsreport_notify2email_id">Выслать уведомление по почте</label></td>
								<td><input type=text name="notifyemail"></td>
								</tr>
								<tr>
								<td><input type="checkbox" name="notify2sms" id="flowsreport_notify2sms_id"></td>
								<td colspan=2><label for="flowsreport_notify2sms_id">Послать уведомление как SMS на телефон</label></td>
								<td><select name="operator">
									<option value="+38067">+38 067 (Киевстар)
									<option value="+38050">+38 050 (UMC)
								</td>
								<td><input type=text name="notifycell"></td>
								</tr>
								</table>
							</td>	
						</tr>
						<tr bgcolor=white><th colspan=2><input type=submit value="Готово"></th>
						</tr>
					<input type="hidden" name="report_type" value="<?echo $report_type?>">
					<input type="hidden" name="client_id" value="<?echo $client_id?>">
					<input type="hidden" name="cl_login" value="<?echo $login?>">
					</form>
					</table>
				</td>
			</tr>
	<?}?>
		</table>
	</td>
 </tr>
 <?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
