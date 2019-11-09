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
<h2><a href="index.php">Admin</a> &gt; Master Schedule</h2>
<table border>
<tr><th>Date/Time</th><th>ID</th><th>Shift</th><th colspan='10'>People</th></tr>
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
  //return $a['start-time'] - $b['start-time'];
  if ($a['start-time'] == $b['start-time']) {
    return 0;
  }
  if ($a['start-time'] < $b['start-time']) {
    return -1;
  }
  return 1;
}

function sortshift2($a, $b)
{
  return $a['id'] - $b['id'];
}

uasort($shifts, 'sortshift2');

$query = "SELECT * FROM parent_shifts";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($arr = $result->fetch_array()) {
    $parentid = $arr['parentid'];
    $shiftid = $arr['shiftid'];
    $parent_shifts[$shiftid][$parentid] = 1;
  }
  $result->close();
}

$query = "SELECT * FROM snow_shifts";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($arr = $result->fetch_array()) {
    $parentid = $arr['parentid'];
    $shiftid = $arr['shiftid'];
    $snow_shifts[$shiftid][$parentid] = 1;
  }
  $result->close();
}

$query = "SELECT * FROM scout_shifts";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($arr = $result->fetch_array()) {
    $scoutid = $arr['scoutid'];
    $shiftid = $arr['shiftid'];
    $scout_shifts[$shiftid][$scoutid] = 1;
  }
  $result->close();
}

foreach ($shifts as $shiftid => $shift) {
  if ($shiftid > 1000) {
    continue;
  }
  print "<tr>";
  $numadults = $shift['adults'];
  $numscouts = $shift['scouts'];
  $numsnow = $LIMITS['fps'];

  $end_str = "";
  if ($shift['type'] == $SHIFT_CASH) {
    $start_str = strftime("%a, %b %e, %l:%M%P", $shift['start-time']);
    $is_cash = true;
  } else {
    $start_str = strftime("%a, %b %e, %l:%M%P-", $shift['start-time']);
    $end_str = trim(strftime("%l:%M%P", $shift['end-time'])); // trim any leading %l space
  }

  if ($shift['description'] != "Open Sales") {
    $numsnow = 0;
  }

  print "<td rowspan='1'>" . $start_str . $end_str . "</td>";
  print "<td rowspan='1'>" . $shift['id'] . "</td>";
  print "<td rowspan='1'>" . $shift['description'] . "</td>";

  if (isset($parent_shifts[$shiftid])) {
    $theparents = $parent_shifts[$shiftid];
    foreach ($theparents as $parentid => $parent) {
      $numadults--;
      print "<td>" . $parents[$parentid]['pname'] . "</td>";
    }
  }
  while ($numadults--) {
    print "<td><i style='color:red'>adult</i></td>";
  }
  print "</tr><tr>";

  print "<td>&nbsp;</td>";
  print "<td>&nbsp;</td>";
  print "<td>&nbsp;</td>";

  if (isset($scout_shifts[$shiftid])) {
    $thescouts = $scout_shifts[$shiftid];
    foreach ($thescouts as $scoutid => $scout) {
      $numscouts--;
      print "<td>" . $scouts[$scoutid]['sname'] . "</td>";
    }
  }
  while ($numscouts--) {
    print "<td><i style='color:red'>scout</i></td>";
  }
  print "</tr>";
}
?>
</table>
<br/>
<table border>
<tr><th>Date</th><th>Troop</th><th>Opener</th><th>Phone</th><th>Closer</th><th>Phone</th></tr>

<?php
foreach ($shifts as $shiftid => $shift) {
  if ($shiftid < 1000) {
    continue;
  }
  if ($shiftid > 2000) {
    continue;
  }
  $end_str = "";
  if ($shift['desc'] == "Cash Open") {
    print "<tr><td>";
    print strftime("%m/%d", $shift['start-time']);
    print "</td><td>60</td><td>";
    if (isset($parent_shifts[$shiftid])) {
      $theparents = $parent_shifts[$shiftid];
      foreach ($theparents as $parentid => $parent) {
        print $parents[$parentid]['pname'];
      }
    }
    print "</td><td>XXX-XXX-XXXX</td>";
    print "<td>";
    if (isset($parent_shifts[$shiftid+1000])) {
      $theparents = $parent_shifts[$shiftid+1000];
      foreach ($theparents as $parentid => $parent) {
        print $parents[$parentid]['pname'];
      }
    }
    print "</td><td>XXX-XXX-XXXX</td>";
    print "<td>";
    if (isset($parent_shifts[$shiftid+2000])) {
      $theparents = $parent_shifts[$shiftid+2000];
      foreach ($theparents as $parentid => $parent) {
        print $parents[$parentid]['pname'];
      }
    }
    print "</td><td>XXX-XXX-XXXX</td></tr>\n";
  }
}


?>
</table>
</div>
</div>
</body>
</html>
