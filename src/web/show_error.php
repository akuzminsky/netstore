<?
 $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
 readfile("$DOCUMENT_ROOT/head.html");
 require("../etc/config.inc.php");
 $msg = $_GET["error"];
?>
 <tr>
 	<td align="center" valign="top">
 		<table title="������� � �����ͦ" width="100%">
		<caption>�� ��� ������ ������� ������� �������</caption>
 			<tr>
				<td><h3><font color="red"><? echo $msg; ?></font></h3></td>
			</tr>
 			<tr>
				<td><a href="index.php">�� �������</a></td>
			</tr>
 			<tr>
				<td><a href="mailto:<? echo $noc_email;?>?body=<? echo $msg; ?>">�������� ���� ��ͦΦ�������� �������</a></td>
			</tr>
		 </table>
	</td>
 </tr>
<?
 readfile("$DOCUMENT_ROOT/bottom.html");
?>
