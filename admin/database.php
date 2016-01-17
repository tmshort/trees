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
<h2><a href="index.php">Admin</a> &gt; Database</h2>
<h3>Upload Schedule</h3>
<form action="upload_schedule.php" method="post" enctype="multipart/form-data">
<label for="file">Schedule (.xls)</label>
<input type="file" name="file" id="file"><br />
<label for="sheet">Troop Sheet name</label>
<input type="text" name="sheet" id="sheet" value="Master Schedule"><br />
<label for="cash">Cash Sheet name</label>
<input type="text" name="cash" id="cash" value="Cash Box"><br />
<input type="submit" name="submit" value="Submit">
</form>

<h3>Upload Roster</h3>
<form action="upload_roster.php" method="post" enctype="multipart/form-data">
<label for="file">Roster (.xls)</label>
<input type="file" name="file" id="file"><br />
<input type="submit" name="submit" value="Submit">
</form>

<h3>Reset Database</h3>
<form action="reset_db.php" method="post">
<label for="force">Reset</label>
<input type="checkbox" name="force" id="force"><br />
<input type="submit" name="submit" value="Submit">
</form>
<h3>Options</h3>
<form action="options.php" method="post">
<?php
$mysqli = db_connect();
if ($mysqli !== FALSE) {
  $query = "SELECT * FROM options";
  if (($result = $mysqli->query($query)) !== FALSE) {
    if ($row = $result->fetch_array()) {
      print "<label for='Start'>Start</label><input type='text' name='start' value='" . $row['opt_start'] . "'><br/>\n";
      print "<label for='Cash'>Cash Shift</label><input type='text' name='cash' value='" . $row['opt_cash'] . "'> (default=3)<br/>\n";
      print "<label for='AddOnly'>Add-Only (no removing shifts)</label><input type='checkbox' name='addonly'";
      if ($row['opt_addonly']) {
	print " checked='checked'";
      }
      print "> (default=off)<br/>\n";

      print "<label for='Continue'>Continue (to next page automatically)</label><input type='checkbox' name='continue'";
      if ($row['opt_continue']) {
	print " checked='checked'";
      }
      print "> (default=on)<br/>\n";

      print "<label for='Disabled'>Disabled (no access allowed)</label><input type='checkbox' name='disabled'";
      if ($row['opt_disabled']) {
	print " checked='checked'";
      }
      print ">((default=on)<br/>\n";

      print "<label for='Nag'>Nag email to schedule</label><input type='checkbox' name='nag'";
      if ($row['opt_nag']) {
	print " checked='checked'";
      }
      print "> (default=on)<br/>\n";

      print "<label for='Min'>Min shifts per person</label><input type='text' name='min' value='" . $row['opt_min'] . "'> (default=0 no-effect)<br/>\n";
      print "<label for='Min'>Min shifts per scout</label><input type='text' name='min_s' value='" . $row['opt_min_s'] . "'> (default=0 no-effect)<br/>\n";
      print "<label for='Min'>Min shifts per parent</label><input type='text' name='min_p' value='" . $row['opt_min_p'] . "'> (default=0 no-effect)<br/>\n";
    }
  }
}
?>
<input type="submit" name="submit" value="Submit">
</form>
</div>
</div>
</body>
</html>



