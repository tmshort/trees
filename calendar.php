<!DOCTYPE HTML>
<?php
include "userauth.php";
include "analyze.php";
include "shifttypes.php";
?>
<html>
<head>
<title>Calendar Display</title>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<link href="/trees.css" rel="stylesheet" type="text/css">
</head>
<body>
<div id="header">
  <div style="float:left;width:50%"><h2><?php href("/home.php"); ?>Home</a> &gt; Calendar Display (All Shifts)</h2></div>
  <div style="float:right;width:50%"><h2 style='text-align:right;color:green'>Green shifts are yours</h2></div>
</div>
<div class="colmask fullpage">
<div class="col1">
<style>
#calendar { font-family: Arial, Helvetica, sans-serif; border-collapse:collapse; }
#calendar td, #calendar th { font-size: 1em; border: 2px solid black; padding: 2px 2px 2px 2px; }
#calendar td { vertical-align: top; height: 4em; }
#calendar th { font-size: 1.2em; text-align: left; }
#calendar th.day { text-align: center }
#calendar th.month { background-color: navy; color: white }
#calendar .weekend { background-color: #d0d0d0 }
#calendar .today { background-color: cyan }
.hidden { visibility: hidden }
.family { color: green; font-weight: bold }
.shift { margin-bottom: 0.5em }
</style>
<table id='calendar'>
<?php
//*********************************************************
//*                                                       *
//* THIS SHOULD REALLY TAKE INTO ACCOUNT TODAY'S DATE!!!! *
//*                                                       *
//*********************************************************

function sortshift($a, $b)
{
  if ($a['start-time'] == $b['start-time']) {
    return $a['type'] - $b['type'];
  }
  if ($a['start-time'] < $b['start-time']) {
    return -1;
  }
  return 1;
}

$mysqli = db_connect();
if ($mysqli === FALSE) {
  print "<b>Error connecting to database</b><br/>\n";
}
$query = "SELECT * FROM shifts";
if (($result = $mysqli->query($query)) === FALSE) {
  print "<b>Error retrieving shifts</b><br/>\n";
} else {
  while (($arr = $result->fetch_array())) {
    $idx = $arr['id'];
    $shifts[$idx] = $arr;
    $shifts[$idx]['start-time'] = strtotime($arr['start']);
    $shifts[$idx]['end-time'] = strtotime($arr['end']);
  }
}

// Get names, current shifts, limits
// parent is already in $PARENT
// scouts are already in $SCOUT[]
$LIMITS = analyze();
$num_scouts = count($SCOUT);

$id = $PARENT['id'];
$query = "SELECT * FROM parent_shifts WHERE parentid = '$id'";
if (($result = $mysqli->query($query)) !== FALSE) {
  while (($arr = $result->fetch_array())) {
    $family_shifts[$arr['shiftid']] = 1;
  }
}
$query = "SELECT * FROM snow_shifts WHERE parentid = '$id'";
if (($result = $mysqli->query($query)) !== FALSE) {
  while (($arr = $result->fetch_array())) {
    $family_shifts[$arr['shiftid']] = 1;
  }
}
foreach ($SCOUT as $scoutid => $value) {
  $query = "SELECT * FROM scout_shifts WHERE scoutid = '$scoutid'";
  if (($result = $mysqli->query($query)) !== FALSE) {
    while (($arr = $result->fetch_array())) {
      $family_shifts[$arr['shiftid']] = 1;
    }
  }
}

uasort($shifts, 'sortshift');

//
// CALENDAR
//

$ONEDAY = 24 * 60 * 60;

reset($shifts);
$first_shift = current($shifts);
$first_time = localtime($first_shift['start-time'], TRUE);
$last_shift = end($shifts);
$date_end = $last_shift['end-time'] + $ONEDAY;
//print "DATE_END: $date_end";
reset($shifts);

// take the date, subtract the day number to determine the start of the week
// on a sunday
$date_start = $first_shift['start-time'] - ($first_time['tm_wday'] * $ONEDAY);

// We ALWAYS start late November...
print "<tr><th class='month' colspan='7'>";
print strftime("%B %Y", $first_shift['start-time']);
print "</th></tr>\n";
print "<tr><th class='day weekend'>Sunday</th><th class='day'>Monday</th><th class='day'>Tuesday</th><th class='day'>Wednesday</th>";
print "<th class='day'>Thursday</th><th class='day'>Friday</th><th class='day weekend'>Saturday</th></tr>\n";

$tmnow = localtime($now, TRUE);

$curmonth = $first_time['tm_mon'];
for ($thedate = $date_start; $thedate <= $date_end; $thedate += $ONEDAY)
{
  $tmdate = localtime($thedate, TRUE);
  if ($curmonth != $tmdate['tm_mon']) {
    if ($tmdate['tm_wday'] != 0) {
      // close out row if not Sunday
      print "</tr>";
    }
    print "<tr><th colspan='7' class='month'>";
    print strftime("%B %Y", $thedate);
    print "</td></tr>";

    if ($tmdate['tm_wday'] != 0) {
      // start row if not Sunday
      print "<tr><td class='hidden' colspan='" . $tmdate['tm_wday'] . "'></td>";
    }

    $curmonth = $tmdate['tm_mon'];
  }
  if ($tmdate['tm_wday'] == 0) {
    print "<tr>";
  }
  if ($tmdate['tm_yday'] == $tmnow['tm_yday']) {
     print "<td class='today'>";
  } else if ($tmdate['tm_wday'] == 0 || $tmdate['tm_wday'] == 6) {
     print "<td class='weekend'>";
  } else {
     print "<td>";
  }
  print "<b>" . $tmdate['tm_mday'] . "</b><br/>";

  while (1) {
    $current_shift = current($shifts);
    $current_time = localtime($current_shift['start-time'], TRUE);
    if (isset($family_shifts[$current_shift['id']])) {
      $style_start = "<div class='family shift'>";
      $style_end = "</div>";
    } else {
      $style_start = "<div class='shift'>";
      $style_end = "</div>";
    }
    if ($tmdate['tm_yday'] == $current_time['tm_yday'] && $tmdate['tm_year'] == $current_time['tm_year']) {
      print $style_start;
      if ($current_shift['type'] == $SHIFT_CASH_OPEN) {
	$start_str = strftime("%l:%M%P", $current_shift['start-time']);
	print "Cash Open $start_str";
      } else if ($current_shift['type'] == $SHIFT_CASH_CLOSE) {
	$start_str = strftime("%l:%M%P", $current_shift['start-time']);
	print "Cash Close $start_str";
      } else {
	$start_str = strftime("%l:%M%P-", $current_shift['start-time']);
	$end_str = trim(strftime("%l:%M%P", $current_shift['end-time'])); // trim any leading %l space
        print $start_str . $end_str;
      }
      print $style_end;
      next($shifts);
      // in case there's another match
      continue;
    } else {
      // not a match
      break;
    }
  }

  print "</td>";
  if ($tmdate['tm_wday'] == 6) {
    print "</tr>";
  }
}

?>
</div>
</div>
</body>
</html>