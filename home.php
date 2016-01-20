<!DOCTYPE HTML>
<?php
include "userauth.php";
include "analyze.php";
include "shifttypes.php";
?>
<html>
<head>
<title>Tree Schedule Home Page</title>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<link href="../trees.css" rel="stylesheet" type="text/css">
<style>
#tselect { border-collapse: collapse; border: 0px solid black; }
th { text-align: left }
td, th { padding: 2px 2px 2px 2px; }
tr:nth-child(even) { background: #DDDDDD }
tr:nth-child(odd) { background: #FFFFFF }
</style>
</head>
<body>
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.0";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<div id="header">
<h2><?php if (!empty($ADMIN)) {
  print "<a href='/admin/index.php'>Admin</a> &gt; ";
  print "<a href='/admin/list.php'>List Parents</a> &gt; ";
}
?>Home</h2>
</div>
<div class="colmask fullpage">
<div class="col1">
<?php 
$LIMITS = analyze();
print "<p>Hello " . $PARENT['pname'] . ".</p>\n";
$IS_SSL = false;
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
  $IS_SSL = true;
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

// Get the list of shifts
$mysqli = db_connect();
if ($mysqli === FALSE) {
  print "<b>Error connecting to database</b><br/>\n";
}

$ENABLED = false;
$query = "SELECT * FROM options WHERE opt_start < NOW()";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($row = $result->fetch_array()) {
    $ENABLED = true;
  }
  $result->close();
}
$query = "SELECT opt_start FROM options";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($row = $result->fetch_array()) {
    $ts = strtotime($row['opt_start']);
    $START = strftime("%l:%M %p %A, %B %e, %Y", $ts);
  }
  $result->close();
}

$query = "SELECT * FROM shifts WHERE troop = '$TROOP'";
if (($result = $mysqli->query($query)) === FALSE) {
  print "<b>Error retrieving shifts</b><br/>\n";
} else {
  while (($arr = $result->fetch_array())) {
    $idx = $arr['id'];
    $shifts[$idx] = $arr;
    $shifts[$idx]['start-time'] = strtotime($arr['start']);
    $shifts[$idx]['end-time'] = strtotime($arr['end']);
  }
  $result->close();
}
uasort($shifts, 'sortshift');

if (isset($SCOUT)) {
  $num_scouts = count($SCOUT);
} else {
  $num_scouts = 0;
}
$minimum_shifts = 0;
$sum_shifts = 0;
if ($LIMITS['min'] > 0) {
   $minimum_shifts = (1 + $num_scouts) * $LIMITS['min'];
}

// Get names, current shifts, limits
// parent is already in $PARENT
// scouts are already in $SCOUT[]

// if $scout_shifts and $parent_shifts are empty (because we didn't POST), then read
// the shifts from the database, if there was an error from above, these should be
// non-zero, so we *should* only read them when first coming to this form
$id = $PARENT['id'];
$query = "SELECT * FROM parent_shifts WHERE parentid = '$id'";
if (($result = $mysqli->query($query)) !== FALSE) {
    while ($row = $result->fetch_array()) {
        $parent_shifts[$row['shiftid']] = 1;
    }
    $result->close();
}
$query = "SELECT * FROM snow_shifts WHERE parentid = '$id'";
if (($result = $mysqli->query($query)) !== FALSE) {
    while ($row = $result->fetch_array()) {
        $snow_shifts[$row['shiftid']] = 1;
    }
    $result->close();
}

if (isset($SCOUT)) {
  foreach ($SCOUT as $id => $value) {
    $query = "SELECT * FROM scout_shifts WHERE scoutid = '$id'";
    if (($result = $mysqli->query($query)) !== FALSE) {
      while ($row = $result->fetch_array()) {
        $scout_shifts[$row['scoutid']][$row['shiftid']] = 1;
      }
      $result->close();
    }
  }
}

print "<ul>\n<li>";
href("/select.php");
if ($ENABLED) {
  print "View and Edit Schedule</a></li>\n<li>";
} else {
  print "View and Edit Schedule</a> -- <b>Starts: $START</b></li>\n<li>";
}
href("/calendar.php");
print "Calendar Display of all the Troop's shifts</a></li>\n";
if (isset($scout_shifts) || isset($parent_shifts)) {
  print "<li>";
  href("/ics.php");
  print "Download calendar (ics) file of schedule</a></li>\n";
}
print "<li>";
href("/instructions.php");
print "Shift instructions</a></li>\n";
print "</ul>\n";

if (isset($scout_shifts) || isset($parent_shifts)) {
    print "<table id='tselect'>\n";
    print "<tr><th>Date/Time</th><th>Description</th><th>Who</th></tr>\n";
}

foreach ($shifts as $shiftid => $shift)
{
    $found = false;
    if (isset($parent_shifts[$shiftid])) {
        $found = true;
    }
    if (isset($snow_shifts[$shiftid])) {
      $found = true;
    }
    if (isset($SCOUT)) {
      foreach ($SCOUT as $scoutid => $scout) {
        if (isset($scout_shifts[$scoutid][$shiftid])) {
          if (isset($counts[$scoutid])) {
            $counts[$scoutid]++;
          } else {
            $counts[$scoutid] = 1;
          }
          $found = true;
        }
      }
    }
    if ($found == false) {
       continue;
    }

    $start = localtime($shift['start-time'], true);
    $end = localtime($shift['end-time'], true);

    // take the date, subtract the day number to determine the start of the week

    $start_str = strftime("%a, %b %e, %l:%M%P", $shift['start-time']);
    if ($start['tm_hour'] == $end['tm_hour']) {
      // Cash shift
      $end_str = "";
    } else {
      $end_str = "-" . trim(strftime("%l:%M%P", $shift['end-time'])); // trim any leading %l space
    }

    print "<tr>";
    print "<td>" . $start_str . $end_str . "</td>";
    print "<td><b>" . $shift['description'] . "</b></td>\n<td>";
    $printed = false;
    if (isset($parent_shifts[$shiftid])) {
        print $PARENT['pname'];
        $printed = true;
    }
    foreach ($SCOUT as $scoutid => $scout) {
        if (isset($scout_shifts[$scoutid][$shiftid])) {
            if ($printed) {
                print " + ";
            }
            print $scout['sname'];
            $printed = true;
        }
    }
    if (isset($snow_shifts[$shiftid])) {
      if ($printed) {
	print " + ";
      }
      print "Snow Removal";
      $printed = true;
    }
    print "</td></tr>\n";
}
$needshifts = false;
$shiftmsg = "";

if (isset($parent_shifts) || isset($scout_shifts) || isset($snow_shifts)) {
    print "</table><br>\n";
} else {
    $shiftmsg .= "<p><b style='color:red'>You have no shifts!!!!</b></p>\n";
}

// Do it as units of $CASH_VALUE (3s)
$parent_count = 0;
if (isset($parent_shifts)) {
  foreach ($parent_shifts as $shiftid => $one) {
    if ($shifts[$shiftid]['type'] == $SHIFT_CASH_OPEN ||
	$shifts[$shiftid]['type'] == $SHIFT_CASH_CLOSE) {
      $parent_count++;
    } else {
      $parent_count += $CASH_VALUE;
    }
  }
}

$sum_shifts = $parent_count / $CASH_VALUE;

if ($parent_count < ($LIMITS['spp'] * $CASH_VALUE)) {
  $need = ($LIMITS['spp'] * $CASH_VALUE) - $parent_count;
  $whole = floor($need/$CASH_VALUE);
  $frac = $need - ($whole * $CASH_VALUE);
  $shiftmsg .= "<p><b style='color:red'>" . $PARENT['pname'] . " (or other parent/adult) needs at least";
  if ($whole > 0) {
    $shiftmsg .= " $whole";
  }
  if ($frac != 0) {
    $shiftmsg .= " $frac/$CASH_VALUE";
  }
  $shiftmsg .= " more shift";
  if ($whole > 1 || $frac != 0) {
    $shiftmsg .= "s";
  }
  $shiftmsg .= ".</b></p>\n";
  $needshifts = true;
}

if (isset($SCOUT)) {
  foreach ($SCOUT as $scoutid => $scout) {
    if (isset($counts[$scoutid])) {
      $scout_count = $counts[$scoutid];
    } else {
      $scout_count = 0;
    }
    $sum_shifts += $scout_count;
    if ($scout_count < $LIMITS['sps']) {
      $shiftmsg .= "<p><b style='color:red'>" . $scout['sname'] . " needs at least " . ($LIMITS['sps'] - $scout_count) . " more shift";
      if (($LIMITS['sps'] - $scout_count) > 1) {
	$shiftmsg .= "s";
      }
      $shiftmsg .= " .</b></p>\n";
      $needshifts = true;
    }
  }
}

if ($sum_shifts < $minimum_shifts) {
  $shiftmsg .= "<p><b style='color:red'>Overall, your family needs " . ($minimum_shifts - $sum_shifts) . " more shift(s) (not counting snow removal).</b></p>";
  $needshifts = true;
}

if (!isset($snow_shifts)) {
  $shiftmsg .= "<p><b style='color:red'>Your family needs 1 snow removal shift.</b></p>";
  $needshifts = true;
}

if ($needshifts) {
  print "<p>Scouts are to fill at least <b>" . $LIMITS['sps'] . " shifts</b>, parents are to fill at least <b>" . $LIMITS['spp'] . " shifts</b>. ";
  if ($minimum_shifts > 0) {
    print "Overall, your family needs to fill <b>" . $minimum_shifts . " total shifts</b> (parent shifts + scout shifts). ";
  }
  print "Each family needs to fill a snow removal shift. You will receive a daily nag email until you fill these requirements.</p>\n";
  print $shiftmsg;
} else {
  print "<p><b style='color:green'>You have completed your schedule!</b></p>\n";
}

?>
<p style="font-weight:bold">Spread the word: Like the <a href='https://www.facebook.com/SudburyBoyScoutTreeSale'>Sudbury Boy Scout Tree Sale</a> on Facebook!</p>
<div class="fb-like" data-href="https://www.facebook.com/SudburyBoyScoutTreeSale" data-layout="standard" data-action="like" data-show-faces="true" data-share="true"></div>
</div>
</div>
</body>
</html>
