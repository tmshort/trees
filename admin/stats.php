<?php
include "auth.inc";
include "../mysql.php";
include "../shifttypes.php";
include "../analyze.php";
$LIMITS = analyze();
?><html>
<head>
<title>Tree Scheduling Admin</title>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<link href="../trees.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="colmask fullpage">
<div class="col1">
<h2><a href="index.php">Admin</a> &gt; Statistics</h2>
<?php
date_default_timezone_set("America/New_York");

$mysqli = db_connect();
if ($mysqli === FALSE) {
  print "<b>Error connecting to database</b><br/>\n";
}

$query = "SELECT * FROM parents";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($arr = $result->fetch_array()) {
    $idx = $arr['id'];
    $parents[$idx] = $arr;
  }
  $result->close();
}

$query = "SELECT * FROM scouts";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($arr = $result->fetch_array()) {
    $idx = $arr['id'];
    $scouts[$idx] = $arr;
  }
  $result->close();
}

$query = "SELECT * FROM shifts";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($arr = $result->fetch_array()) {
    $idx = $arr['id'];
    $shifts[$idx] = $arr;
    $shifts[$idx]['start-time'] = strtotime($arr['start']);
    $shifts[$idx]['end-time'] = strtotime($arr['end']);
  }
  $result->close();
}

function sortshift($a, $b)
{
  return $a['start-time'] - $b['start-time'];
  if ($a['start-time'] == $b['start-time']) {
    return 0;
  }
  if ($a['start-time'] < $b['start-time']) {
    return -1;
  }
  return 1;
}

uasort($shifts, 'sortshift');

$query = "SELECT * FROM parent_shifts";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($arr = $result->fetch_array()) {
    $parentid = $arr['parentid'];
    $shiftid = $arr['shiftid'];
    $parent_shifts[$parentid][$shiftid] = 1;
  }
  $result->close();
}

$query = "SELECT * FROM snow_shifts";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($arr = $result->fetch_array()) {
    $parentid = $arr['parentid'];
    $shiftid = $arr['shiftid'];
    $snow_shifts[$parentid][$shiftid] = 1;
  }
  $result->close();
}

$query = "SELECT * FROM scout_shifts";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($arr = $result->fetch_array()) {
    $scoutid = $arr['scoutid'];
    $shiftid = $arr['shiftid'];
    $scout_shifts[$scoutid][$shiftid] = 1;
  }
  $result->close();
}

$CASH_VALUE = 3;
$query = "SELECT * FROM options";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($arr = $result->fetch_array()) {
    $CASH_VALUE = $arr['opt_cash'];
  }
}

$total_families = 0;
$started_families = 0;
foreach ($parents as $parentid => $parent) {
  $total_families++;
  if (isset($parent_shifts[$parentid])) {
    $started_families++;
  }
}

$total_shifts = 0;
foreach ($shifts as $shiftid => $shift) {
  if ($shift['type'] == $SHIFT_CASH) {
    $total_shifts++;
  } else {
    $total_shifts += $CASH_VALUE * ($shift['scouts'] + $shift['adults']);
  }
}
$total_shifts /= $CASH_VALUE;

$filled_shifts = 0;
foreach ($parent_shifts as $parentid => $shift_temp) {
  foreach ($shift_temp as $shiftid => $one) {
    if ($shifts[$shiftid]['type'] == $SHIFT_CASH) {
      $filled_shifts++;
    } else {
      $filled_shifts += $CASH_VALUE;
    }
  }
}

foreach ($scout_shifts as $scouttid => $shift_temp) {
  foreach ($shift_temp as $shiftid => $one) {
    if ($shifts[$shiftid]['type'] == $SHIFT_CASH) {
      $filled_shifts++;
    } else {
      $filled_shifts += $CASH_VALUE;
    }
  }
}
$filled_shifts /= $CASH_VALUE;

print "Total families: $total_families<br>\n";
print "Started families: $started_families<br>\n";
$percent = (100 * $started_families) / $total_families;
print "Percent: $percent%<br>\n";
print "Total Shifts: $total_shifts<br>\n";
print "Filled Shifts: $filled_shifts<br>\n";
$percent = (100 * $filled_shifts) / $total_shifts;
print "Percent: $percent%<br>\n";
?>
</div>
</div>
</body>
</html>
