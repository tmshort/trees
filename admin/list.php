<?php
include "auth.inc";
include "../mysql.php";
?><html>
<head>
<title>Tree Scheduling Admin</title>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<link href="../trees.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="colmask fullpage">
<div class="col1">
<h2><a href="index.php">Admin</a> &gt; Parent List</h2>
<ol>
<?php

$mysqli = db_connect();
if ($mysqli === FALSE) {
  print "<b>Error connecting to database</b><br/>\n";
}

$query = "SELECT * FROM parents";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($row = $result->fetch_array()) {
    print "<li><a href='../home.php/" . $row['password'] . "?ADMIN'>" . $row['pname'] . "</a></li>";
  }
  $result->close();
}

?>
</ol>
</div>
</div>
</body>
</html>



