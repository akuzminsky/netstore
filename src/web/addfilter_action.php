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
  if(!empty($_POST["save"])){
    mysql_query("BEGIN");
    $handler = mysql_escape_string($_POST["handler"]);
    $limit = mysql_escape_string($_POST["limit"]);
    $filter_id = mysql_escape_string($_POST["filter_id"]);
    $client_id = mysql_escape_string($_POST["client_id"]);
    $query = "INSERT INTO `filter_action`(`filter_id`, `limit`, `handler`) 
    VALUES('$filter_id', '$limit', '$handler')";
    if(FALSE == mysql_query($query)){
      header ("Location: show_error.php?error=".mysql_error($mysql));
      mysql_query("ROLLBACK");
      mysql_close($mysql);
      exit(1);
      }
		log_event($mysql, $client_id, "filter_action", "", "add", "Created filter action for filter id $filter_id\nQuery: $query");
    mysql_query("COMMIT");
    header("Location: show_client.php?client_id=".$client_id);
    exit(0);
    }
  else{
    $client_id = mysql_escape_string($_GET["client_id"]);
?>
  <tr>
    <td valign="top" align="center">
      <form action="<? echo $_SERVER['PHP_SELF']; ?>" method="POST">
      <input type="hidden" name="client_id" value="<? echo $client_id;?>">
      <table border="0" valign="top">
        <tr>
          <th>Фильтр</th>
          <th>Лимит в байтах</th>
          <th>Действие</th>
        </tr>
        <tr>
          <td>
            <select name="filter_id">
<?
  $query = "select id, description from filter where client_id = '$client_id'";
  $result = mysql_query($query);
  if(FALSE == $result){
    header ("Location: show_error.php?error=".mysql_error($mysql));
    mysql_close($mysql);
    exit(-1);
    }
  $n = mysql_num_rows($result);
  for($i = 0; $i < $n; $i++){
?>
              <option value="<? echo mysql_result($result, $i, "id");?>"><? echo mysql_result($result, $i, "description");?></option>
<?
    }
?>
            </select>
          </td>
          <td><input type="text" name="limit" value="0"></td>
          <td>
            <select name="handler">
<?
  $query = "select handler, description from filter_handler";
  $result = mysql_query($query);
  if(FALSE == $result){
    header ("Location: show_error.php?error=".mysql_error($mysql));
    mysql_close($mysql);
    exit(-1);
    }
  $n = mysql_num_rows($result);
  for($i = 0; $i < $n; $i++){
?>
              <option value="<? echo mysql_result($result, $i, "handler")?>"><? echo mysql_result($result, $i, "description"); ?></option>
<?
    }
?>
            </select>
          </td>
        </tr>
        <tr>
          <th colspan="3"><input type="submit" name="save" value="Добавить..."></th>
        </tr>
      </table>
      </form>
    </td>
  </tr>
<?
    }
  mysql_close($mysql);
  readfile("$DOCUMENT_ROOT/bottom.html");
?>
