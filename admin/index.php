<?php
include "auth.inc";
include "../mysql.php";
include "../shifttypes.php";
include "../analyze.php";
include "svcpie.php";
?><html>
<head>
<title>Tree Scheduling Admin</title>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<link href="../trees.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="colmask fullpage">
<div class="col1">
<h2><a href='../index.php'>Home</a> &gt; Admin</h2>
<table>
<tr><td>
<ul>
<li style="font-weight:bold">Reports</li>
<ul>
<li><a href="list.php">List all the parents</a></li>
<li><a href="bydate.php">Schedule by date</a></li>
<li><a href="byfamily.php">Schedule by person</a></li>
<li><a href="nag.php">Nag Report</a></li>
<li><a href="bydate2.php">Master schedule format</a></li>
</ul>
<li style="font-weight:bold">Actions</li>
<ul>
    <li><a href="areyousure.php">Send email</a></li>
    <li><a href="database.php">Database</a></li>
</ul>
</td><td>
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

$CV = 3;
$query = "SELECT * FROM options";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($arr = $result->fetch_array()) {
    $CV = 0 + $arr['opt_cash'];
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

$total_adult = 0;
$total_scout = 0;
foreach ($shifts as $shiftid => $shift) {
  if ($shift['type'] == $SHIFT_CASH_OPEN ||
      $shift['type'] == $SHIFT_CASH_CLOSE) {
    $total_adult++;
  } else {
    $total_adult += $CV * $shift['adults'];
    $total_scout += $CV * $shift['scouts'];
  }
}
$total_shifts = $total_adult + $total_scout;

$filled_adult = 0;
$filled_scout = 0;
foreach ($parent_shifts as $parentid => $shift_temp) {
  foreach ($shift_temp as $shiftid => $one) {
    if ($shifts[$shiftid]['type'] == $SHIFT_CASH_OPEN ||
        $shifts[$shiftid]['type'] == $SHIFT_CASH_CLOSE) {
      $filled_adult++;
    } else {
      $filled_adult += $CV;
    }
  }
}

foreach ($scout_shifts as $scouttid => $shift_temp) {
  foreach ($shift_temp as $shiftid => $one) {
    if ($shifts[$shiftid]['type'] == $SHIFT_CASH_OPEN ||
        $shifts[$shiftid]['type'] == $SHIFT_CASH_CLOSE) {
      $filled_scout++;
    } else {
      $filled_scout += $CV;
    }
  }
}
$filled_shifts = $filled_adult + $filled_scout;

$arr = analyze();

$people = $arr['scouts'] + $arr['parents'];
$shifts = $arr['scout_shifts'] + $arr['parent_shifts'];
$shifts_per_person = $shifts / $people;
$spp = ceil($shifts_per_person);

print "<a style='font-weight:bold' href='stats.php'>Quick stats</a><br/>\n";

$percent = round((100 * $started_families) / $total_families, 2);
print "Families: <b>$percent%</b> ($started_families of $total_families) (i.e. what parents haven't started their own?)<br/>\n";

$percent = round((100 * $filled_shifts) / $total_shifts, 2);
print "Shifts: <b>$percent%</b> (";
print fraction_string($filled_shifts, $CV);
print " of ";
print fraction_string($total_shifts, $CV);
print ")<br/>\n";
$d1 = $percent * 100;

$percent = round((100 * $filled_scout) / $total_scout, 2);
print "Scout Shifts: <b>$percent%</b> (";
print fraction_string($filled_scout, $CV);
print " of ";
print fraction_string($total_scout, $CV);
print ")";
if ($filled_scout != $total_scout) {
  print " =&gt; " . fraction_string($total_scout - $filled_scout, $CV) . " left, ";
  print fraction_string(($arr['scouts'] * $CV * $arr['sps']) - $filled_scout, $CV);
  print " available to fill";
}
print "<br/>\n";
$d2 = $percent * 100;

$percent = round((100 * $filled_adult) / $total_adult, 2);
print "Parent Shifts: <b>$percent%</b> (";
print fraction_string($filled_adult, $CV);
print " of ";
print fraction_string($total_adult, $CV);
print ")";
if ($filled_adult != $total_adult) {
  print " =&gt; " . fraction_string($total_adult - $filled_adult, $CV) . " left, ";
  print fraction_string(($arr['parents'] * $CV * $arr['spp']) - $filled_adult, $CV);
  print " available to fill";
}
print "<br/>\n";
$d3 = $percent * 100;

if ($arr['min'] != 0) {
  print "Extra Shifts:";
  print " Total: " . fraction_string($total_shifts, $CV);
  print ", Filled: " . fraction_string($filled_shifts, $CV);
  $capacity = $people * $arr['min'];
  print ", Capacity: " . $capacity;
  $need = $total_shifts - $filled_shifts;
  print ", Need: " . fraction_string($need, $CV);
  $available = ($capacity * $CV) - $filled_shifts;
  print ", Available: " . fraction_string($available, $CV);
  $extra = $available - $need;
  print ", Extra: " . fraction_string($extra, $CV);
  print "<br/>\n";
}

function fraction_string ($num, $den)
{
  $num = round($num);
  $den = round($den);
  $str = "";
  if ($num < 0) {
    $str .= "-";
    $num = -$num;
  }
  $x = floor($num/$den);
  if ($x > 0) {
    $str .= floor($num/$den);
  }
  $x = $num % $den;
  if ($x != 0) {
    $str .= "<sup>$x</sup>/<sub>$den</sub>";
  }
  return $str;
}

print "<a style='font-weight:bold' href='analysis.php'>Analysis</a><br/>";

print "Parents: " . $arr['parents'] . ", shifts: " . fraction_string($arr['parent_shifts']*$CV,$CV);
print " =&gt; " . round($arr['shifts_per_parent'], 4) . " =&gt; " . $arr['spp'];
print " (slop " . fraction_string((($arr['spp'] * $arr['parents']) - $arr['parent_shifts'])*$CV,$CV) . ")";
print " (" . round($arr['parent_shifts'] / $arr['spp'], 4). " parents to fill)";
if ($arr['min_p'] > 0) {
  print " override = " . $arr['min_p']; 
}
print "<br/>\n";
print "Scouts: " . $arr['scouts'] . ", shifts: " . $arr['scout_shifts'];
print " =&gt; " . round($arr['shifts_per_scout'], 4) . " =&gt; " . $arr['sps'];
print " (slop " . round(($arr['sps'] * $arr['scouts']) - $arr['scout_shifts'], 4) . ")";
print " (" . round($arr['scout_shifts'] / $arr['sps'], 4). " scouts to fill)";
if ($arr['min_s'] > 0) {
  print " override = " . $arr['min_s'];
}
print "<br/>\n";

print "Everyone: $people, shifts: " . fraction_string($shifts * $CV, $CV);
print " =&gt; " . round($shifts_per_person, 4) . " =&gt; " . $spp;
$slop = (($spp * $people) - $shifts);
print " (slop " . fraction_string($slop * $CV, $CV) . ")";
print " (" . round($shifts / $spp, 4). " people to fill)";
if ($arr['min'] > 0) {
  print " override = " . $arr['min'];
}
print "<br/>\n";
?>
</td>
</table>
<table><?php
print "<tr><th>All Shifts " . $d1/100 . "%</th><th>Scout Shifts " . $d2/100 . "%</th><th>Parent Shifts " . $d3/100 . "%</th></tr>"
?><tr>
<td><?php
$width = 200; // canvas size
$height = 200; 
$centerx = $width / 2; // centre of the pie chart
$centery = $height / 2;
$radius = min($centerx, $centery) - 10; // radius of the pie chart
$data[] = $d1;
$data[] = 10000 - $d1 ? 10000 - $d1 : 1;
print "<svg xmlns:svg='http://www.w3.org/2000/svg' xmlns='http://www.w3.org/2000/svg' version='1.0' width='$width' height='$height'>";
print piechart($data, $centerx, $centery, $radius);
print "</svg>";
?></td>
<td><?php
$width = 200; // canvas size
$height = 200; 
$centerx = $width / 2; // centre of the pie chart
$centery = $height / 2;
$radius = min($centerx, $centery) - 10; // radius of the pie chart
$data1[] = $d2;
$data1[] = 10000 - $d2 ? 10000 - $d2 : 1;
print "<svg xmlns:svg='http://www.w3.org/2000/svg' xmlns='http://www.w3.org/2000/svg' version='1.0' width='$width' height='$height'>";
print piechart($data1, $centerx, $centery, $radius);
print "</svg>";
?></td>
<td><?php
$width = 200; // canvas size
$height = 200; 
$centerx = $width / 2; // centre of the pie chart
$centery = $height / 2;
$radius = min($centerx, $centery) - 10; // radius of the pie chart
$data2[] = $d3;
$data2[] = 10000 - $d3 ? 10000 - $d3 : 1;
print "<svg xmlns:svg='http://www.w3.org/2000/svg' xmlns='http://www.w3.org/2000/svg' version='1.0' width='$width' height='$height'>";
print piechart($data2, $centerx, $centery, $radius);
print "</svg>";
?></td>
</tr>
</table>
</div>
</div>
</body>
</html>



