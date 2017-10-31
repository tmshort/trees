<?php
include "auth.inc";
include "../mysql.php";
?><html>
<head>
<title>Options</title>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<link href="../trees.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="colmask fullpage">
<div class="col1">
<h2><a href="index.php">Admin</a> &gt; <a href="database.php">Database</a> &gt; Options</h2>
<?php

$mysqli = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
  print "<b>" . $mysqli->error . "</b>";
if ($mysqli->connect_errno) {
  print "<b>Failed to connect to MySQL: " . $mysqli->connect_error . "</b>\n";
  exit;
}
  
$mysqli->select_db("trees");

if ($result = $mysqli->query("SELECT DATABASE()")) {
  $row = $result->fetch_row();
  print "Database: <b>" . $row[0] . "</b><br/>";
  $result->close();
} else {
  print "<b>" . $mysqli->error . "</b>";
}

// defaults
$opt_continue = 0;
if (isset($_POST['continue'])) {
  $opt_continue = 1;
}
$opt_addonly = 0;
if (isset($_POST['addonly'])) {
  $opt_addonly = 1;
}
$opt_disabled = 0;
if (isset($_POST['disabled'])) {
  $opt_disabled = 1;
}
$opt_cash = 3;
if (isset($_POST['cash'])) {
  $opt_cash = $mysqli->escape_string($_POST['cash']);
}
$opt_min = 0;
if (isset($_POST['min'])) {
  $opt_min = $mysqli->escape_string($_POST['min']);
}
$opt_min_p = 0;
if (isset($_POST['min_p'])) {
  $opt_min_p = $mysqli->escape_string($_POST['min_p']);
}
$opt_min_s = 0;
if (isset($_POST['min_s'])) {
  $opt_min_s = $mysqli->escape_string($_POST['min_s']);
}
$opt_nag = 0;
if (isset($_POST['nag'])) {
  $opt_nag = 1;
}
$opt_start = '2020-01-01 10:00:00';
if (isset($_POST['start'])) {
  date_default_timezone_set("America/New_York");
  $ts = strtotime($_POST['start']);
  $opt_start = date("Y-m-d H:i:s", $ts);
}
$opt_name = $DEFNAME;
if (isset($_POST['name'])) {
  $opt_name = $mysqli->escape_string($_POST['name']);
}
$opt_email = $DEFEMAIL;
if (isset($_POST['email'])) {
  $opt_email = $mysqli->escape_string($_POST['email']);
}


$query = "UPDATE options SET ";
$query .= "opt_continue = " . $opt_continue;
$query .= ", opt_addonly = " . $opt_addonly;
$query .= ", opt_disabled = " . $opt_disabled;
$query .= ", opt_cash = " . $opt_cash;
$query .= ", opt_min = " . $opt_min;
$query .= ", opt_min_s = " . $opt_min_s;
$query .= ", opt_min_p = " . $opt_min_p;
$query .= ", opt_nag = " . $opt_nag;
$query .= ", opt_start = '$opt_start'";
$query .= ", opt_name = '$opt_name'";
$query .= ", opt_email = '$opt_email'";
$query .= ";";

//print "Query: $query";

if ($mysqli->query($query) === FALSE) {
  print "<b>Failed to create table options " . $mysqli->error . "</b>\n";
  exit;
}

print "<b>Database updated!</b>\n";

$mysqli->close();
?>
<a href="database.php">Back to database page</a>
</div>
</div>
</body>
</html>



