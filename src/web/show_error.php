<?
 $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
 readfile("$DOCUMENT_ROOT/head.html");
 require("../etc/config.inc.php");
 $msg = $_GET["error"];
?>
 <tr>
 	<td align="center" valign="top">
 		<table title="Помилка в систем╕" width="100%">
		<caption>П╕д час роботи системи виникла помилка</caption>
 			<tr>
				<td><h3><font color="red"><? echo $msg; ?></font></h3></td>
			</tr>
 			<tr>
				<td><a href="index.php">На початок</a></td>
			</tr>
 			<tr>
				<td><a href="mailto:<? echo $noc_email;?>?body=<? echo $msg; ?>">Написати лист адм╕н╕стратору системи</a></td>
			</tr>
		 </table>
	</td>
 </tr>
<?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
