<?php
include "../mysql.php";
include "../shifttypes.php";
include "../analyze.php";
?><html>
<head>
<title>Tree Scheduling Admin</title>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<link href="../trees.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="colmask fullpage">
<div class="col1">
<h2><a href="index.php">Admin</a> &gt; Family Report</h2>
<p><a href="nag.php">Go to nag page</a></p>
<?php
$LIMITS = analyze();
ini_set('sendmail_from', 'troop60trees@gmail.com');
date_default_timezone_set("America/New_York");

$mysqli = db_connect();
if ($mysqli === FALSE) {
  print "Error connecting to database\n";
  exit;
}

$disabled = 0;
$CASH_VALUE = 3;
$query = "SELECT * FROM options";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($arr = $result->fetch_array()) {
    $CASH_VALUE = $arr['opt_cash'];
    if ($arr['opt_nag'] == 0) {
      $disabled = 1;
    }
  }
  $result->close();
}

if ($disabled == 1) {
  exit;
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

$stats = analyze();

print "<table>";
print "<tr>";
print "<th>Name</th>";
print "<th>Adult Shifts</th>";
print "<th>Scout Shifts</th>";
if ($stats['min'] > 0) {
  print "<th>Total Shifts</th>";
}
print "<th>Snow Shift</th>\n";
print "</th>";

foreach ($parents as $parentid => $parent) {
   $complete = true;
   $total_shifts = 0;
   $total_people = 1; // the parent
   $snow_state = "<td style='color:green'>OK</td>";
   $parent_state = "<td style='color:green'>OK</td>";
   $scout_state = "";

   $numshifts = 0;
   if (isset($parent_shifts[$parentid])) {
     $theshifts = $parent_shifts[$parentid];
     foreach ($theshifts as $shiftid => $one) {
       $shift = $shifts[$shiftid];
       if ($shift['type'] == $SHIFT_CASH_OPEN) {
	 $numshifts++;
       } else if ($shift['type'] == $SHIFT_CASH_CLOSE) {
	 $numshifts++;
       } else {
	 $numshifts += $CASH_VALUE;
       }
     }
   }

   $has_snow = false;
   if (isset($snow_shifts[$parentid])) {
     $theshifts = $snow_shifts[$parentid];
     foreach ($theshifts as $shiftid => $one) {
       $has_snow = true;
     }
   }
   if ($numshifts < ($LIMITS['spp'] * $CASH_VALUE)) {
     $need = (($LIMITS['spp'] * $CASH_VALUE) - $numshifts) / $CASH_VALUE;
     $parent_state = "<td style='color:red'>Needs " . round($need, 2) . " shift(s)</td>\n";
     $complete = false;
   }
   if (!$has_snow) {
     $snow_state = "<td style='color:red'>Needed</td>\n";
     $complete = false;
   }

   $total_shifts += $numshifts / $CASH_VALUE;

   foreach ($scouts as $scoutid => $scout) {
     if ($scout['email'] != $parent['email']) {
       continue;
     }

     $numshifts = 0;
     if (isset($scout_shifts[$scoutid])) {
       $theshifts = $scout_shifts[$scoutid];
       foreach ($theshifts as $shiftid => $one) {
	 $numshifts++;
       }
     }
     if ($numshifts < $LIMITS['sps']) {
       $need = $LIMITS['sps'] - $numshifts;
       $name = $scout['sname'];
       if (!empty($scout_state)) {
	 $scout_state .= "<br/>";
       }
       $scout_state .= "<span style='color:red'>$name needs $need shift(s)</span>\n";
       $complete = false;
     }
     $total_shifts += $numshifts;
     $total_people++;
   }

   if ($LIMITS['min'] > 0) {
     $need = $LIMITS['min'] * $total_people;
     if ($total_shifts < $need) {
       $total_state = "<td style='color:red'>Needs " . round($need - $total_shifts, 2) . " shift(s)</td>";
       $complete = false;
     } else {
       $total_state = "<td style='color:green'>OK</td>";
     }
   } else {
     $total_state = "";
   }

   if (1) {
     if (empty($scout_state)) {
       $scout_state = "<span style='color:green'>OK</span>";
     }

     print "<tr>";
     print "<th><a href='../home.php/" . $parent['password'] . "?ADMIN'>" . $parent['pname'] . "</a></th>";
     print "$parent_state";
     print "<td>$scout_state</td>\n";
     if ($stats['min'] > 0) {
       print "$total_state\n";
     }
     print "$snow_state\n";
     print "</tr>\n";
   }
}
print "</table>";
?>
</div>
</div>
</body>
</html>
