<!DOCTYPE HTML>
<?php
require_once "mysql.php";
include "analyze.php";
include "admin/email_user.php";
ini_set('sendmail_from', 'troop60trees@gmail.com');
?><html>
<head>
<title>Start Start Tree Scheduling</title>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<link href="/trees.css" rel="stylesheet" type="text/css">
</head>
<body>
<div id="header">
<h2><a href="index.php">Start Tree Shift Scheduling</a> &gt; Send Email</h2>
</div>
<div class="colmask fullpage">
<div class="col1">
  <p>Please check your email for a message from troop60trees@gmail.com with a start link in it (search your junk and spam folder too!). You will need to use that link to start your schedule.</p>
  <p>If you do not receive an email, please <a href="index.php">try again</a> and carefully type in your email.</p>
  <p>For privacy reasons, this page does not confirm success or failure.</p>
<?php
$pathparts = pathinfo($_SERVER['SCRIPT_NAME']);
$dirname = $pathparts['dirname'];
$START = "now";
if (isset($_POST['email'])) {
  $email = strtolower($_POST['email']);
  $mysqli = db_connect();
  if ($mysqli !== FALSE) {
    $query = "SELECT opt_start FROM options";
    if (($result = $mysqli->query($query)) !== FALSE) {
      while ($row = $result->fetch_array()) {
        $ts = strtotime($row['opt_start']);
        $START = strftime("%l:%M %p %A, %B %e, %Y", $ts);
      }
      $result->close();
    }
    $email = $mysqli->escape_string($email);
    $theemail = $email;
    $query = "SELECT * FROM emails WHERE email = '$email'";
    if (($result = $mysqli->query($query)) !== FALSE) {
      while ($row = $result->fetch_array()) {
	$email = $row['pemail'];
      }
    }
    $query = "SELECT * FROM parents WHERE email = '$email'";
    if (($result = $mysqli->query($query)) !== FALSE) {
      if (($arr = $result->fetch_array())) {
	send_the_email($arr['pname'], $arr['password'], $email, $dirname, $theemail);
      } else {
	error_log("No match on $email?");
      }
    } else {
      error_log("Unable to query " . $email . ": " . $mysqli->error);
    }
    $result->close();
  } else {
    error_log("Unable to load database");
  }
}
?>
</div>
</div>
</body>
</html>
