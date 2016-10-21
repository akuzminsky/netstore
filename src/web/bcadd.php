<?
  $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
  readfile("$DOCUMENT_ROOT/head.html");
  session_start();
  if(!@session_is_registered('authdata') || $_SESSION['authdata']['permlevel'] != 'admin'){
    header($start_url);
    }
  if($_SESSION['authdata']['permlevel'] != 'admin'){
    header($start_url);
    }
  $authdata = $_SESSION['authdata'];
  include("$DOCUMENT_ROOT/netstorecore.php");
  include("top.php");
  $login = $authdata['login'];
  $passwd = $authdata['passwd'];
  $mysql = @mysql_connect($host, $login, $passwd); mysql_set_charset("koi8u");
  if($mysql == FALSE){
    header ("Location: show_error.php?error=".mysql_error());
    exit(1);
    }
  if(FALSE == mysql_select_db($db)){
    header ("Location: show_error.php?error=".mysql_error($mysql));
    mysql_close($mysql);
    exit(1);
    }
  if(empty($_POST)){
?>
  <tr>
    <td valign="top" align="center">
      <form action="<? echo $_SERVER['PHP_SELF']; ?>" method="POST">
      <table>
        <tr>
          <th>Назва б╕знес-центру</th>
        </tr>
        <tr>
          <th><input type="text" name="description"></th>
        </tr>
        <tr>
          <th colspan="2"><input type="submit" value="Добавити" class="button"></th>
        </tr>
      </table>
      </form>
    </td>
  </tr>
<?
    }
  else{
    $description = mysql_escape_string($_POST["description"]);
    $query = "insert into cluster(description, creating_time)
        values('$description', NOW())";
    if(FALSE == mysql_query($query)){
      header ("Location: show_error.php?error=".mysql_error($mysql));
      mysql_close($mysql);
      exit(1);
      }
		log_event($mysql, "0", "cluster", mysql_insert_id(), "add", "Created cluster '$description'");
    mysql_close($mysql);
    header("Location: show_bc.php");
		exit(0);
    }
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
