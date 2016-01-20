<?php
require_once "mysql.php";
$ADMIN="";
$error = 0;
$path_info = "";
$mysqli = db_connect();
if ($mysqli === FALSE) {
  $error = 1;
}

if (!isset($_SERVER['PATH_INFO'])) {
  $error = 4;
}

if ($error == 0) {
  $PATHINFO = $mysqli->escape_string(substr($_SERVER['PATH_INFO'], 1));
  $query = "SELECT * FROM parents WHERE password = '$PATHINFO'";
  if (($result = $mysqli->query($query)) === FALSE) {
    $error = 2;
  }
}
if ($error == 0) {
  if (isset($_GET['ADMIN'])) {
    $ADMIN = "?ADMIN";
  }
  if (isset($_POST['ADMIN'])) {
    $ADMIN = "?ADMIN";
  }
}
if ($error == 0) {
  if (!($arr = $result->fetch_array())) {
    $error = 3;
  } else {
    $PARENT = $arr;
  }
}
if (isset($PARENT)) {
  $email = $mysqli->escape_string($PARENT['email']);
  $TROOP = $mysqli->escape_string($PARENT['troop']);
  $query = "SELECT * FROM scouts WHERE email = '$email' AND teoop = '$troop'";
  if (($result = $mysqli->query($query)) === FALSE) {
    $error = 4;
  }
} else {
  $error = 5;
}
if ($error == 0) {
  while ($arr = $result->fetch_array()) {
    $id = $arr['id'];
    $SCOUT[$id] = $arr;
  }
}

$CASH_VALUE = 3; // default
$query = "SELECT * FROM options";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($arr = $result->fetch_array()) {
    if ($arr['opt_continue'] == 1) {
      $AUTO_CONTINUE = true;
    }
    if ($arr['opt_addonly'] == 1) {
      $ADD_ONLY = true;
    }
    if ($arr['opt_disabled'] == 1 && empty($ADMIN)) {
      $error = "The site is temporarily DISABLED";
    }
    $CASH_VALUE = $arr['opt_cash'];
  }
}

unset($mysqli);
if ($error !== 0) {
?>
<html>
<head>
<title>Unauthorized</title>
</head>
<body>
    <h2>Unauthorized <?php print $error ?></h2>
</body>
</html>
<?php
    exit;
}
$self = $_SERVER['SCRIPT_NAME'];
$pathparts = pathinfo($self);
unset($self);
$DIRNAME = $pathparts['dirname'];
if ($DIRNAME == "/") {
  $DIRNAME = "";
}
$PATHNAME = $pathparts['basename'];
unset($pathparts);

function href($uri)
{
  global $DIRNAME, $PATHINFO, $ADMIN;
  print '<a href="' . $DIRNAME . $uri . '/' . $PATHINFO . $ADMIN . '">';
}

function redirect($uri)
{
  global $DIRNAME, $PATHINFO, $ADMIN;
  print $DIRNAME . $uri . '/' . $PATHINFO . $ADMIN;
}

function action($uri)
{
  global $DIRNAME, $PATHINFO, $ADMIN;
  print 'action="' . $DIRNAME . $uri . '/' . $PATHINFO . $ADMIN . '"';
}

date_default_timezone_set("America/New_York");
// Use these to test time stuff.
$now = time();
//$now = strtotime("Dec 11, 2013 11:00am");
//$now = strtotime("Oct 11, 2013 11:00am");

?>