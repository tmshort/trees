<!DOCTYPE HTML>
<html>
<head>
<title>Start Start Tree Scheduling</title>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<link href="trees.css" rel="stylesheet" type="text/css">
</head>
<body>
<div id="header">
<h2>Start Tree Shift Scheduling</h2>
</div>
<div class="colmask fullpage">
<div class="col1">

<?php
require_once "mysql.php";
$error = "";
$mysqli = db_connect();
if ($mysqli !== FALSE) {
  $query = "SELECT * FROM options";
  if (($result = $mysqli->query($query)) !== FALSE) {
    while ($arr = $result->fetch_array()) {
      if ($arr['opt_disabled'] == 1) {
	$error = "<p>The site is temporarily DISABLED.</p>";
      }
    }
  }
  unset($mysqli);
}

if (empty($error)) {
?>
<p>To start the scheduling process, please enter your email you have registered with the Troop.</p>
<form method="post" action="sendemail.php">
<label for="email">Email</label>
<input type="text" name="email" id="email">
<input type="submit" name="submit" value="Submit">
</form>
<?php 
} else {
   print($error);
} 
?>
<p>This domain is not for sale.</p>
</div>
</div>
</body>
</html>
