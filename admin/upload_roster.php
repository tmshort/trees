<?php
include "auth.inc";
include "../mysql.php";
include "password.php";
require __DIR__ . "/../vendor/autoload.php";
use PHPExcel;
use PHPExclIOFactory;
?><html>
<head>
<title>Upload Roster</title>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<link href="../trees.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="colmask fullpage">
<div class="col1">
<h2><a href="index.php">Admin</a> &gt; <a href="database.php">Database</a> &gt; Upload Roster</h2>
<?php
    $file = "";
    if ($_FILES["file"]["error"] > 0) {
      echo "Error: " . $_FILES["file"]["error"] . "<br />";
    } else {
      echo "Upload: " . $_FILES["file"]["name"] . "<br />";
      echo "Type: " . $_FILES["file"]["type"] . "<br />";
      echo "Size: " . $_FILES["file"]["size"] . "<br />";
      echo "Location: " . $_FILES["file"]["tmp_name"] . "<br />";
      $file = $_FILES["file"]["tmp_name"];
    }
?>
<table>
<?php
date_default_timezone_set('UTC');
if (!empty($file)) {

  $objReader = PHPExcel_IOFactory::createReaderForFile($file);
  $objReader->setReadDataOnly(true);
  $objReader->setLoadSheetsOnly(array("Troop 60 Roster"));
  $objPHPExcel = $objReader->load($file);
  $objWorksheet = $objPHPExcel->getActiveSheet();

  $priorshift = 0;

  // Big assumption
  // Rows have:
  // A = Scout last name**
  // B = Scout first name**
  // C = Grade = 5 - 12 (anything else, ignore)**
  // D = Scout Email
  // E = Address
  // F = Home Phone
  // G = Primary Parent**
  // H = Primary Parent Cell
  // I = Primary Parent Email**
  // J = Secondary Parent
  // K = Secondary Parent Cell
  // L = Secondary Parent Email
  // ** means required information
  //    Key for anything would be ScoutLastName.ScoutFirstName
  //    Also need parents info, to indicate number of families

  $GRADE_MIN = 5;
  $GRADE_MAX = 12;

  $highestRow = $objWorksheet->getHighestRow();
  
  for ($row = 1; $row <= $highestRow; $row++) {
    $grade = $objWorksheet->getCell('C' . $row)->getValue();
    if ($GRADE_MIN <= $grade && $grade <= $GRADE_MAX) {
      $lastname = $objWorksheet->getCell('A' . $row)->getValue();
      $firstname = $objWorksheet->getCell('B' . $row)->getValue();
      $parent = $objWorksheet->getCell('G' . $row)->getValue();
      $email = $objWorksheet->getCell('I' . $row)->getValue();
      $email2 = $objWorksheet->getCell('L' . $row)->getValue();
      $email3 = $objWorksheet->getCell('D' . $row)->getValue();
      if (!empty($firstname) && !empty($lastname) && !empty($parent) && !empty($email)) {
	$key = "$lastname|$firstname";
	$scout[$key]['name'] = "$firstname $lastname";
	$scout[$key]['fname'] = $firstname;
	$scout[$key]['parent'] = $parent;
	$scout[$key]['email'] = $email;
	$scouts[] = $key;
	$parents[$email]['name'] = $parent;
	$parents[$email]['password'] = generatePassword(32);
	$emails[$email] = $email;
        if (!empty($email2)) {
	  $emails[$email2] = $email;
	}
        if (!empty($email3)) {
	  $emails[$email3] = $email;
	}
      }
    }
  }
  
  unlink($file);
}
?>
</table>
<?php
print "<pre>\n";
  print_r($scout);
  print_r($scouts);
  print_r($parents);
print "</pre>\n";

print "<b>Connecting to database...</b><br/>\n";
$mysqli = db_connect();
if ($mysqli === FALSE) {
  print "<b>Unable to load database</b><br/>\n";
}

print "<b>Loading scouts...</b><br/>\n";
foreach ($scout as $key => $value) {
  $query = "INSERT INTO scouts (sname, fname, pname, email) VALUES (";
  $query .= "'" . $mysqli->escape_string($value['name']) . "', ";
  $query .= "'" . $mysqli->escape_string($value['fname']) . "', ";
  $query .= "'" . $mysqli->escape_string($value['parent']) . "', ";
  $query .= "'" . $mysqli->escape_string(strtolower($value['email'])) . "')";
  print "QUERY: " . htmlentities($query) . "<br/>\n";
  if ($mysqli->query($query) === FALSE) {
    print "<b>Unable to add scout " . htmlentities($value['name']) . ": " . htmlentities($mysqli->error) . "</b><br/>\n";
  }
}

print "<b>Loading parents...</b><br/>\n";
foreach ($parents as $key => $value) {
  $query = "INSERT INTO parents (pname, email, password) VALUES (";
  $query .= "'" . $mysqli->escape_string($value['name']) . "', ";
  $query .= "'" . $mysqli->escape_string(strtolower($key)) . "', ";
  $query .= "'" . $mysqli->escape_string($value['password']) . "')";
  print "QUERY: " . htmlentities($query) . "<br/>\n";
  if ($mysqli->query($query) === FALSE) {
    print "<b>Unable to add parent " . htmlentities($value['name']) . ": " . htmlentities($mysqli->error) . "</b><br/>\n";
  }
}

print "<b>Loading emails...</b><br/>\n";
foreach ($emails as $key => $value) {
  $query = "INSERT INTO emails (pemail, email) VALUES (";
  $query .= "'" . $mysqli->escape_string(strtolower($value)) . "', ";
  $query .= "'" . $mysqli->escape_string(strtolower($key)) . "')";
  print "QUERY: " . htmlentities($query) . "<br/>\n";
  if ($mysqli->query($query) === FALSE) {
    print "<b>Unable to add email " . htmlentities($value) . ": " . htmlentities($mysqli->error) . "</b><br/>\n";
  }
}


?>
</div>
</div>
</body>
</html>



