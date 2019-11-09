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
<h2><a href="index.php">Admin</a> &gt; Schedule by Person</h2>
<table border>
<tr><th>Person</th><th>Date/Time - Shift</th><th>Status</th></tr>
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

print "<tr><th colspan=3>Parents</th></tr>\n";

foreach ($parents as $parentid => $parent) {

  print "<tr><td>" . $parent['pname'] . "</td><td>";
  
  $numshifts = 0;
  if (isset($parent_shifts[$parentid])) {
    $theshifts = $parent_shifts[$parentid];
    foreach ($theshifts as $shiftid => $one) {
      $shift = $shifts[$shiftid];
      $end_str = "";
      if ($shift['type'] == $SHIFT_CASH) {
	$start_str = strftime("%a, %b %e, %l:%M%P", $shift['start-time']);
	$is_cash = true;
	$numshifts++;
      } else {
	$start_str = strftime("%a, %b %e, %l:%M%P-", $shift['start-time']);
	$end_str = trim(strftime("%l:%M%P", $shift['end-time'])); // trim any leading %l space
	$numshifts += $CASH_VALUE;
      }
      print $start_str . $end_str . " - " . $shift['description'] . "<br/>";
    }
  }

  $has_snow = false;
  if (isset($snow_shifts[$parentid])) {
    $theshifts = $snow_shifts[$parentid];
    foreach ($theshifts as $shiftid => $one) {
      $shift = $shifts[$shiftid];
      $end_str = "";
      $start_str = strftime("%a, %b %e, %l:%M%P", $shift['start-time']);
      print $start_str . " - Snow removal<br/>";
      $has_snow = true;
    }
  }
  print "</td>";
  if ($numshifts == ($LIMITS['spp'] * $CASH_VALUE) && $has_snow) {
    print "<td style='color:green'>GOOD";
  } else if ($numshifts >= ($LIMITS['spp'] * $CASH_VALUE) && $has_snow) {
    $numshifts = -((($LIMITS['spp'] * $CASH_VALUE) - $numshifts) / $CASH_VALUE);
    print "<td style='color:blue'>GOOD + $numshifts extra";
  } else {
    $numshifts = (($LIMITS['spp'] * $CASH_VALUE) - $numshifts) / $CASH_VALUE;
    print "<td style='color:red'>";
    if ($numshifts > 0) {
      print "NEEDS " . $numshifts;
      if (!$has_snow) {
	print " + SNOW REMOVAL";
      }
    } else if (!$has_snow) {
      print " NEEDS SNOW REMOVAL";
    }
  }
  print "</td></tr>\n";
}

print "<tr><th colspan=3>Scouts</th></tr>\n";

foreach ($scouts as $scoutid => $scout) {

  print "<tr><td>" . $scout['sname'] . "</td><td>";

  $numshifts = 0;
  if (isset($scout_shifts[$scoutid])) {
    $theshifts = $scout_shifts[$scoutid];
    foreach ($theshifts as $shiftid => $one) {
      $shift = $shifts[$shiftid];
      $end_str = "";
      $start_str = strftime("%a, %b %e, %l:%M%P-", $shift['start-time']);
      $end_str = trim(strftime("%l:%M%P", $shift['end-time'])); // trim any leading %l space
      $numshifts++;

      print $start_str . $end_str . " - " . $shift['description'] . "<br/>";
    }
  }
  print "</td>";
  if ($numshifts == $LIMITS['sps']) {
    print "<td style='color:green'>GOOD";
  } else if ($numshifts > $LIMITS['sps']) {
    $numshifts = $numshifts - $LIMITS['sps'];
    print "<td style='color:blue'>GOOD + $numshifts extra";
  } else {
    $numshifts = $LIMITS['sps'] - $numshifts;
    print "<td style='color:red'>NEEDS " . $numshifts;
  }
  print "</td></tr>\n";
}


?>
</table>
</div>
</div>
</body>
</html>
