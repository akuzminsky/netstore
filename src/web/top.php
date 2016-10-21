<?
  if(!empty($authdata)){
?>
  <tr>
    <td valign="top" align="center" colspan="2">
      <table>
        <tr>
<?
    }
  switch($authdata[permlevel]){
    case "admin":
?>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="admin.php">Адм╕н╕стратор</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_ll.php?cluster_id=all">Кл╕╓нти</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_bc.php">Б╕знес-центри</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="reports.php">Зв╕ти</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="bookkeeping.php">Розрахунки</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="reports.php?type=other">Тестування</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="logout.php">Вийти</a></td>
<?
			break;
    case "accountoperator":
?>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_ll.php?cluster_id=all">Кл╕╓нти</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_bc.php">Б╕знес-центри</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="logout.php">Вийти</a></td>
<?
      break;
    case "support":
?>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_ll.php?cluster_id=all">Кл╕╓нти</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_bc.php">Б╕знес-центри</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="reports.php">Зв╕ти</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="bookkeeping.php">Розрахунки</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="logout.php">Вийти</a></td>
<?
      break;
    case "juniorsupport":
?>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_ll.php?cluster_id=all">Кл╕╓нти</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_bc.php">Б╕знес-центри</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="reports.php">Зв╕ти</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="logout.php">Вийти</a></td>
<?
      break;
    case "manager":
?>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_ll.php?cluster_id=all">Кл╕╓нти</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_bc.php">Б╕знес-центри</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="reports.php">Зв╕ти</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="reports.php?type=other">Тестування</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="logout.php">Вийти</a></td>
<?
      break;
    case "topmanager":
?>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_ll.php?cluster_id=all">Кл╕╓нти</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="show_bc.php">Б╕знес-центри</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="reports.php">Зв╕ти</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="bookkeeping.php">Розрахунки</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="reports.php?type=other">Тестування</a></td>
          <td onmouseover="this.style.backgroundColor='ffff99'" onmouseout="this.style.backgroundColor='ffffff'"><a href="logout.php">Вийти</a></td>
<?
      break;
    case "client":
			$dfrom = mktime(0, 0, 0, date("m"), 1, date("Y"));
			$dto = mktime();	
      $location = "reportpersonal.php?"."cl_login=$authdata[login]&amp;dbegin=$dfrom&amp;dend=$dto";
      if(!(isset($_GET[cl_login]) || isset($_POST[cl_login]))){
        header("Location: $location");
				exit(0);
        }
    }
  if(!empty($authdata)){
?>
        </tr>
      </table>
    </td>
  </tr>
<?
    }
?>
