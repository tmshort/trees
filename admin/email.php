<?php
require_once "../mysql.php";
include "../analyze.php";
ini_set('sendmail_from', 'troop60trees@gmail.com');
?><html>
<head>
<title>Send Emails</title>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<link href="../trees.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="colmask fullpage">
<div class="col1">
<h2><a href="index.php">Admin</a> &gt; Start Tree Shift Scheduling</h2>
<?php

date_default_timezone_set("America/New_York");

include "email_user.php";

$n = 0;
$START = "now";
$MYNAME = $DEFNAME;
$MYEMAIL = $DEFEMAIL;
$pathparts = pathinfo($_SERVER['SCRIPT_NAME']);
$pathparts = pathinfo($pathparts['dirname']);
$dirname = $pathparts['dirname'];

$mysqli = db_connect();
if ($mysqli !== FALSE) {
  $query = "SELECT opt_start, opt_name, opt_email FROM options";
  if (($result = $mysqli->query($query)) !== FALSE) {
    while ($row = $result->fetch_array()) {
      $ts = strtotime($row['opt_start']);
      $START = strftime("%l:%M %p %A, %B %e, %Y", $ts);
      $MYNAME = $row['opt_name'];
      $MYEMAIL = $row['opt_email'];
    }
    $result->close();
  }
  $query = "SELECT * FROM parents";
  if (($result = $mysqli->query($query)) !== FALSE) {
    while (($arr = $result->fetch_array())) {
      $idx = $arr['id'];
      $parents[$idx] = $arr;
    }
  }
  $result->close();
  foreach ($parents as $parentid => $parent) {
    $query = "SELECT email FROM emails WHERE pemail = '" . $parent['email'] . "';";
    if (($result = $mysqli->query($query)) !== FALSE) {
      while ($arr = $result->fetch_array()) {
	send_the_email($parent['pname'], $parent['password'], $arr['email'], $dirname);
	$n++;
	print "$n. Sending mail to " . $parent['pname'] . " (" . $arr['email'] . ")<br/>\n";
      }
    }
  }
  $result->close();
} else {
  print "<b>Unable to load database</b><br/>\n";
}

?>
</div>
</div>
</body>
</html>
