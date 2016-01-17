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
<h2><a href="index.php">Admin</a> &gt; Nag Report</h2>
<?php
$LIMITS = analyze();
ini_set('sendmail_from', 'troop60trees@gmail.com');
date_default_timezone_set("America/New_York");

$mysqli = db_connect();
if ($mysqli === FALSE) {
  print "Error connecting to database\n";
  exit;
}

/* DEAL WITH NAG UPDATE */

if (isset($_POST['submit'])) {
  //foreach ($_POST as $key => $value) {
  //  if (preg_match("/^nag(\d+)$/", $key, $matches)) {
  //    print "Nag: " . $matches[1];
  //  }
  //}
  $query = $MYSQL_LOCK;
  if (($result = $mysqli->query($query)) !== FALSE) {
    while ($row = $result->fetch_row()) {
      if ($row[0] != 1) {
	print "<b>Error locking database.</b><br/>\n";
	error_log("Error locking database.");
	exit;
      }
    }
    $result->close();
  } else {
    print "<b>Error locking database.</b><br/>\n";
    error_log("Error locking database.");
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
  
  $query = "UPDATE parents SET do_not_nag = '0'";
  if ($mysqli->query($query) == FALSE) {
    print "UPDATE parents failed<br>";
    error_log("UPDATE parents failed: $query");
  }
  
  foreach ($parents as $parentid => $parent) {
    if (isset($_POST['nag' . $parentid])) {
      $query = "UPDATE parents SET do_not_nag = '1' WHERE id = '$parentid'";
      if ($mysqli->query($query) == FALSE) {
	print "UPDATE parents failed<br>";
	error_log("UPDATE parents failed: $query");
      }
    }
  }
  
  $query = $MYSQL_UNLOCK;
  if (($result = $mysqli->query($query)) !== FALSE) {
    while ($row = $result->fetch_row()) {
      if ($row[0] != 1) {
	print "<b>Error unlocking database.</b><br/>\n";
	error_log("Error unlocking database.");
	exit;
      }
    }
    $result->close();
  } else {
    print "<b>Error unlocking database.</b><br/>\n";
    error_log("Error unlocking database.");
    exit;
  }
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

print "<form action='nag.php' method='post'>";
print "<table>";
print "<tr>";
print "<th>Name</th>";
print "<th>Do Not Nag</th>";
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
   $nag = "<input type='checkbox' name='nag$parentid'>";
   $scout_state = "";

   if ($parent['do_not_nag'] != 0) {
       $nag = "<input type='checkbox' name='nag$parentid' checked='checked'>";
   }

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

   if (!$complete) {
     if (empty($scout_state)) {
       $scout_state = "<span style='color:green'>OK</span>";
     }

     print "<tr>";
     print "<th><a href='../home.php/" . $parent['password'] . "?ADMIN'>" . $parent['pname'] . "</a></th>";
     print "<th>$nag</th>";
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
print "<input type='submit' name='submit' value='Submit'>";
print "</form>";
?>
</div>
</div>
</body>
</html>