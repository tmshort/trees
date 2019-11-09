<?php
include "auth.inc";
include "../mysql.php";
include "../shifttypes.php";
require_once "Classes/PHPExcel.php";
?><html>
<head>
<title>Upload Tree Schedule</title>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<link href="../trees.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="colmask fullpage">
<div class="col1">
<h2><a href="index.php">Admin</a> &gt; <a href="database.php">Database</a> &gt; Upload Tree Schedule</h2>
<?php
    $file = "";
    $sheet = "";
    $cash = "";
    if ($_FILES["file"]["error"] > 0) {
      echo "Error: " . $_FILES["file"]["error"] . "<br />";
    } else {
      echo "Upload: " . $_FILES["file"]["name"] . "<br />";
      echo "Type: " . $_FILES["file"]["type"] . "<br />";
      echo "Size: " . $_FILES["file"]["size"] . "<br />";
      echo "Location: " . $_FILES["file"]["tmp_name"] . "<br />";
      echo "Sheet: " . $_POST["sheet"] . "<br />";
      $file = $_FILES["file"]["tmp_name"];
      $sheet = $_POST["sheet"];
      $cash = $_POST["cash"];
    }
?>
<table>
<?php
date_default_timezone_set('UTC');
$index = 0;
$cashshifts = 0;
$openings = 0;
$closings = 0;
$middays = 0;
$nonsales = 0;
$totalscouts = 0;
$totaladults = 0;
$ourtroop = 60;

$troopdata["adults60"] = 0;
$troopdata["scouts60"] = 0;
$troopdata["shifts60"] = 0;
$troopdata["setup60"] = 0;
$troopdata["sales60"] = 0;

$troopdata["adults61"] = 0;
$troopdata["scouts61"] = 0;
$troopdata["shifts61"] = 0;
$troopdata["setup61"] = 0;
$troopdata["sales61"] = 0;

$troopdata["adults63"] = 0;
$troopdata["scouts63"] = 0;
$troopdata["shifts63"] = 0;
$troopdata["setup63"] = 0;
$troopdata["sales63"] = 0;

$troopdata["adults65"] = 0;
$troopdata["scouts65"] = 0;
$troopdata["shifts65"] = 0;
$troopdata["setup65"] = 0;
$troopdata["sales65"] = 0;

$troopdata["adults1776"] = 0;
$troopdata["scouts1776"] = 0;
$troopdata["shifts1776"] = 0;
$troopdata["setup1776"] = 0;
$troopdata["sales1776"] = 0;

$troopdata["adults"] = 0;
$troopdata["scouts"] = 0;
$troopdata["shifts"] = 0;
$troopdata["setup"] = 0;
$troopdata["sales"] = 0;

if (!empty($file)) {
  $objReader = PHPExcel_IOFactory::createReaderForFile($file);
  $objReader->setReadDataOnly(true);
}
if (!empty($file) && !empty($sheet)) {

  $objReader->setLoadSheetsOnly(array($sheet));
  $objPHPExcel = $objReader->load($file);
  $objWorksheet = $objPHPExcel->getActiveSheet();

  // New FORMAT!!!!
  // Row 1 = Title, no blank rows
  // Column
  // A = shift number
  // B = Day of Week
  // C = Date
  // D = start time
  // E = end time
  // F = Type
  // G = # of adults
  // H = # of scouts
  // I = Troop/Crew

  $highestRow = $objWorksheet->getHighestRow();
  print "<tr><td colspan=5>ROWS = $highestRow</td></tr>\n";

  for ($row = 1; $row <= $highestRow; $row++) {
    $shift = $objWorksheet->getCell('A' . $row)->getValue();
    $dow = $objWorksheet->getCell('B' . $row)->getValue();
    if ($shift > 0 && !empty($dow)) {
      $adults = $objWorksheet->getCell('G' . $row)->getValue();
      $scouts = $objWorksheet->getCell('H' . $row)->getValue();
      $troop  = $objWorksheet->getCell('I' . $row)->getValue();
      $troopdata["shifts" . $troop]++;
      $troopdata["adults" . $troop] += $adults;
      $troopdata["scouts" . $troop] += $scouts;
      if ($shift >= 100) {
	$troopdata["setup" . $troop]++;
	$troopdata["setup"]++;
      } else {
	$troopdata["sales" . $troop]++;
	$troopdata["sales"]++;
      }
      $troopdata["shifts"]++;
      $troopdata["adults"] += $adults;
      $troopdata["scouts"] += $scouts;
      if ($troop != $ourtroop) {
	continue;
      }

      $index++;
      // sales
      $sheetdata[$index]['shift'] = $shift;
      $sheetdata[$index]['dow'] = $dow = $objWorksheet->getCell('B' . $row)->getValue();
      $date = $objWorksheet->getCell('C' . $row)->getValue();
      $d = new DateTime('1899-12-30');
      $d->add(new DateInterval('P' . $date . 'D'));
      $sheetdata[$index]['chkdate'] = $d->format("D, n/j");
      $date = $sheetdata[$index]['date'] = $d->format("Y-m-d");
      $start = $sheetdata[$index]['start'] = date("H:i:s", (24 * 60 * 60 * $objWorksheet->getCell('D' . $row)->getValue()));
      $end = $sheetdata[$index]['end'] = date("H:i:s", (24 * 60 * 60 * $objWorksheet->getCell('E' . $row)->getValue()));
      $sheetdata[$index]['desc'] = $type =  $objWorksheet->getCell('F' . $row)->getValue();
      $sheetdata[$index]['adults'] = $adults =  $objWorksheet->getCell('G' . $row)->getValue();
      $sheetdata[$index]['scouts'] = $scouts =  $objWorksheet->getCell('H' . $row)->getValue();
      if ($shift >= 100) {
	$sheetdata[$index]['type'] = $SHIFT_SETUP;
	$nonsales++;
      } else {
	$sheetdata[$index]['type'] = $SHIFT_SALES;
	if (strstr($type, "Open") !== FALSE) {
	  $sheetdata[$index]['desc'] = $type = "Open Sales";
	  $openings++;
	} else if (strstr($type, "Close") !== FALSE) {
	  $sheetdata[$index]['desc'] = $type = "Close Sales";
	  $closings++;
	} else {
	  $sheetdata[$index]['desc'] = $type = "Midday Sales";
	  $middays++;
	}
      }
      $totalscouts += $scouts;
      $totaladults += $adults;
    } else {
      // non-shift
      continue;
    }
    print "<tr><td>#$shift</td><td>$dow</td><td>$date</td><td>$start</td><td>$end</td><td>$type</td><td>adults=$adults</td><td>scouts=$scouts</td></tr>\n";
  }
}


if (!empty($file) && !empty($cash)) {
  $objReader->setLoadSheetsOnly(array($cash));
  $objPHPExcel = $objReader->load($file);
  $objWorksheet = $objPHPExcel->getActiveSheet();

  // Big assumption
  // Changed 2019...
  // A = shift number
  // B = Day
  // C = Date
  // D = Unit
  // E = Opener
  // F = Phone
  // G = Mid Day (2pm) - weekends only
  // H = Phone
  // I = Closer
  // J = Phone

  // Need to create two or three rows for each row

  $highestRow = $objWorksheet->getHighestRow();

  for ($row = 1; $row <= $highestRow; $row++) {
    $shift = $objWorksheet->getCell('A' . $row)->getValue();
    $unit = $objWorksheet->getCell('D' . $row)->getValue();
    if ($shift > 0 && $unit == 60) {

      // This date could be in the past, all we care about is month/day
      // Doesn't really work, will fix later
      $date = $objWorksheet->getCell('C' . $row)->getValue();
      $d = new DateTime('1899-12-30');
      $d->add(new DateInterval('P' . $date . 'D'));
      $date = $d->format("Y-m-d");
      $tm = localtime(strtotime($date), TRUE);

      // Open, then mid, then close

      // Open
      $index++;
      $sheetdata[$index]['shift'] = $shift + 1000;
      $sheetdata[$index]['date'] = $date;
      $sheetdata[$index]['desc'] = "Cash Open";
      $sheetdata[$index]['adults'] = 1;
      $sheetdata[$index]['scouts'] = 0;
      $sheetdata[$index]['type'] = $SHIFT_CASH;
      if ($tm['tm_wday'] == 0) { /* Sun */
	$sheetdata[$index]['start'] = $sheetdata[$index]['end'] = "9:00";
      } else if ($tm['tm_wday'] == 6) { /* Sat */
	$sheetdata[$index]['start'] = $sheetdata[$index]['end'] = "9:00";
      } else { /* Mon-Fri */
	$sheetdata[$index]['start'] = $sheetdata[$index]['end'] = "15:30";
      }
      $totaladults++;
      $cashshifts++;

      // Midday
      if ($objWorksheet->getCell('G' . $row)->getValue() == "Adult Name") {
        $index++;
        $sheetdata[$index]['shift'] = $shift + 2000;
        $sheetdata[$index]['date'] = $date;
        $sheetdata[$index]['desc'] = "Cash Midday";
        $sheetdata[$index]['adults'] = 1;
        $sheetdata[$index]['scouts'] = 0;
        $sheetdata[$index]['type'] = $SHIFT_CASH;
	$sheetdata[$index]['start'] = $sheetdata[$index]['end'] = "14:00";
        $totaladults++;
        $cashshifts++;
      }

      // Close
      $index++;
      $sheetdata[$index]['shift'] = $shift + 3000;
      $sheetdata[$index]['date'] = $date;
      $sheetdata[$index]['desc'] = "Cash Close";
      $sheetdata[$index]['adults'] = 1;
      $sheetdata[$index]['scouts'] = 0;
      $sheetdata[$index]['type'] = $SHIFT_CASH;
      if ($tm['tm_wday'] == 0) { /* Sun */
	$sheetdata[$index]['start'] = $sheetdata[$index]['end'] = "19:00";
      } else if ($tm['tm_wday'] == 6) { /* Sat */
	$sheetdata[$index]['start'] = $sheetdata[$index]['end'] = "21:00";
      } else { /* Mon-Fri */
	$sheetdata[$index]['start'] = $sheetdata[$index]['end'] = "20:00";
      }
      $totaladults++;
      $cashshifts++;

    } else {
      // non-shift
      continue;
    }
    print "<tr><td>#$shift</td><td>&nbsp;</td><td>$date</td><td>&nbsp;</td><td>&nbsp;</td><td>CASH</td><td>adults=1</td><td>scouts=0</td></tr>\n";
  }
}

if (!empty($file)) {
  unlink($file);
}
?>
</table>
<?php
print "Opening Shifts: $openings<br/>\n";
print "Closing Shifts: $closings<br/>\n";
print "Midday Shifts: $middays<br/>\n";
print "Cash Shifts: $cashshifts<br/>\n";
print "Total Shifts: " . ($openings + $closings + $middays) . "<br/>\n";
print "Total Scouts: $totalscouts<br/>\n";
print "Total Adults: $totaladults<br/>\n";
print "Total People-Shifts: " . ($totalscouts + $totaladults) . "<br/>\n";
print "<pre>\n";
print_r($troopdata);
print_r($sheetdata);
print "</pre>\n";
print_stats(60, $troopdata);
print_stats(61, $troopdata);
print_stats(63, $troopdata);
print_stats(65, $troopdata);
print_stats(1776, $troopdata);
$mysqli = db_connect();
if ($mysqli === FALSE) {
  print "<b>Unable to load database</b><br/>\n";
}
foreach($sheetdata as $key => $value) {
  $query = "INSERT INTO shifts (id, start, end, description, scouts, adults, type) VALUES (";
  $query .= $value['shift'] . ", ";
  $query .= "'" . $value['date'] . " " . $value['start'] . "', ";
  $query .= "'" . $value['date'] . " " . $value['end'] . "', ";
  $query .= "'" . $value['desc'] . "', ";
  $query .= $value['scouts'] . ", ";
  $query .= $value['adults'] . ", ";
  $query .= $value['type'] . ");";
  print "QUERY: $query<br/>\n";
  if ($mysqli->query($query) === FALSE) {
    print "<b>Unable to add shift " . $value['shift'] . ": " . $mysqli->error . "</b><br/>\n";
  }
}

function print_stats($troop, $troopdata)
{
  print "<b>Unit: $troop</b><br/>\n";
  print "Adults: " . round(100*$troopdata["adults$troop"] / $troopdata["adults"],2) . "%<br/>\n";
  print "Scouts: " . round(100*$troopdata["scouts$troop"] / $troopdata["scouts"],2) . "%<br/>\n";
  print "Shifts: " . round(100*$troopdata["shifts$troop"] / $troopdata["shifts"],2) . "%<br/>\n";
  print "Sales: " . round(100*$troopdata["sales$troop"] / $troopdata["sales"],2) . "%<br/>\n";
  print "Setup: " . round(100*$troopdata["setup$troop"] / $troopdata["setup"],2) . "%<br/>\n";
}
?>
</div>
</div>
</body>
</html>
