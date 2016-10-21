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
 if(isset($_GET[client_id])){
 	$client_id = $_GET[client_id];
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
	$_GET[client_id] = $client_id;
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
			<tr>
				<td valign=top>
				<p><center>Уважаемый пользователь.</center>
				<p>На этой странице Вы можете заказать формирование детальных отчетов использования ресурсов Интернет. 
				Они помогут Вам контролировать уровень потребляемого трафика, прогнозировать его величину.
				С помощью этих отчетов можно будет вовремя выявить подозрительную сетевую активность, а значит пресечь 
				действие вирусов или, например, спамеров рассылающих почту через неправильно настроенные почтовые системы.
				<p>К сожалению, из-за огромных объемов данных нет возможности сделать доступными такие отчеты в on-line 
				режиме. Однако мы убеждены, что Вы имеете право на получение детальной информации об использованной 
				услуге. Эта форма есть компромисс между сложностью обработки больших массивов данных и возможностью
				получения нужной информации.
				<p>Мы предлагаем Вам несколько типовых отчетов с разным уровнем детализации.
					<li> Трафик по дням. Этот очет показывает объем трафика за каждый день.
					<li> Трафик по часам. В этом отчете предоставлена более детальная информация. Объем трафика
						суммируется с часовым интервалом. Т.е. отчет содержит 24 записи для каждого дня из заданного
						периода.
					<li> Полный отчет. Трафик в этом отчете представлен по потокам. Это самая детальная информация,
						которая протоколируется нашей учетной системой. Объем такого отчета
						может быть больше, чем величина использованного трафика. Полезным такой отчет будет для 
						системных администраторов для отладочных целей. Обычному пользователю мы не рекомендуем 
						заказывать такой отчет - работа с ним будет очень затруднительна. После того как отчет 
						будет готов, он вышлется сжатым архивом на указанный Вами адрес электронной почты. 
					<li> Если Вас не устраивают вышепредложенные отчеты, мы выслушаем Ваши предложения 
						и постараемся их реализовать. Ждем от Вас комментариев и предложений по адресу 
						<a href="mailto: noc@nbi.com.ua">noc@nbi.com.ua</a>
						</li>
						<p>После того как Вы заполните данную форму, заказ помещается в очередь и автоматически 
						исполняется в фоновом режиме. Сам отчет может быть выслан Вам на почту либо
						опубликован на сервере статистики. Оповещение о готовом отчете может быть выслано Вам
						по почте либо SMS-сообщением.
						<p>
				</td>
			</tr>
			<tr>
				<td align=center><form action="submit_detailed_report.php" method=post>
					<table border=0>
						<tr><th align=center colspan=2>Выберите тип отчета для заказа
						<tr><td><input type=radio name="report_type" id="radio_daily" value="daily" checked>
							<td><label for="radio_daily">Трафик по дням
						<tr><td><input type=radio name="report_type" id="radio_hourly" value="hourly">
							<td><label for="radio_hourly">Трафик по часам
						<tr><td><input type=radio name="report_type" id="radio_flows" value="flows">
							<td><label for="radio_flows">Трафик по потокам
						<tr><td align=center colspan=2><input type=submit name=subreport value="Далее&gt;&gt;">
					</table>
					<input type=hidden name=client_id value="<?echo $client_id?>">
					<input type=hidden name=cl_login value="<?echo $login?>">
					</form>
				</td>
			</tr>
		</table>
	</td>
 </tr>
 <?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
