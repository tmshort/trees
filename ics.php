<?php
include "userauth.php";
include "analyze.php";
include "shifttypes.php";

header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition:attachment; filename=trees.ics');

function dateToCal($timestamp)
{
  //return date('Ymd\THis\Z', $timestamp);
  return date('Ymd\THis', $timestamp);
}
function escapeString($string)
{
  return preg_replace('/([\,;])/', '\\\$1', $string);
}

function vcalstart()
{
  print "BEGIN:VCALENDAR\n";
  print "VERSION:2.0\n";
  print "PRODID:-//tshort/tree//NONSGML v1.0//EN\n";
  print "CALSCALE:GREGORIAN\n";
}

function vcalend()
{
  print "END:VCALENDAR\n";
}

function vevent($datestart, $dateend, $address, $summary, $description)
{
  print "BEGIN:VEVENT\n";
  print "DTEND:" . dateToCal($dateend) . "\n";
  print "UID:" . uniqid() . "\n";
  print "DTSAMP:" . dateToCal(time()) . "\n";
  print "LOCATION:" . escapeString($address) . "\n";
  print "DESCRIPTION:" . escapeString($description) . "\n";
  print "SUMMARY:" . escapeString($summary) . "\n";
  print "DTSTART:" . dateToCal($datestart) . "\n";
  print "END:VEVENT\n";
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
  $result->close();
}

$num_scouts = count($SCOUT);
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
foreach ($SCOUT as $id => $value) {
    $query = "SELECT * FROM scout_shifts WHERE scoutid = '$id'";
    if (($result = $mysqli->query($query)) !== FALSE) {
        while ($row = $result->fetch_array()) {
            $scout_shifts[$row['scoutid']][$row['shiftid']] = 1;
        }
        $result->close();
    }
}

vcalstart();

foreach ($shifts as $shiftid => $shift)
{
    $found = false;
    if (isset($parent_shifts[$shiftid])) {
        $found = true;
    }
    if (isset($snow_shifts[$shiftid])) {
        $found = true;
    }
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
    if ($found == false) {
       continue;
    }

    $people = "";
    if (isset($parent_shifts[$shiftid])) {
        $people = $PARENT['pname'];
    }
    foreach ($SCOUT as $scoutid => $scout) {
      if (isset($scout_shifts[$scoutid][$shiftid])) {
	if (!empty($people)) {
	  $people .= " + ";
	}
	$people .= $scout['sname'];
      }
    }
    if (isset($snow_shifts[$shiftid])) {
      if (!empty($people)) {
	$people .= " + ";
      }
      $people = "Snow Removal";
    }
    vevent($shift['start-time'],
	   $shift['end-time'], 
	   "Sullivan Tire Parking Lot", 
	   $shift['description'],
	   $people);
}

vcalend();

?>
