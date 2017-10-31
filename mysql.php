<?php
require_once "load_config.inc";

/* Sets up mysql */
$MYSQL_LOCK = "SELECT GET_LOCK('update', 10)";
$MYSQL_UNLOCK = "SELECT RELEASE_LOCK('update')";


function db_connect()
{
  global $MYSQL_HOST;
  global $MYSQL_USER;
  global $MYSQL_PASS;
  $mysqli = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
  if ($mysqli->connect_errno) {
    print "<b>Failed to connect to MySQL: " . $mysqli->connect_error . "</b><br/>\n";
    return FALSE;
  }

  if ($mysqli->select_db("trees") === FALSE) {
      print "<b>select_db() failed</b><br/>\n";
      $mysqli->close();
      return FALSE;
  }
  $result = $mysqli->query("SELECT DATABASE()");
  if ($result !== FALSE) {
    $row = $result->fetch_row();
    if ($row[0] != "trees") {
      print "<b>Unable to select database</b><br/>\n";
      $mysqli->close();
      return FALSE;
    }
  } else {
    print "<b>SELECT DATABASE() failed</b><br/>\n";
    $mysqli->close();
    return FALSE;
  }
  return $mysqli;
}
?>