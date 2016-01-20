<!DOCTYPE HTML>
<?php
include "userauth.php";
include "analyze.php";
include "shifttypes.php";
$AUTO_CONTINUE = 1;
// character used to X the calendar
$X = "&#10004;";
?>
<html>
<head>
<title>View and Edit Schedule</title>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<link href="/trees.css" rel="stylesheet" type="text/css">
</head>
<body>
<div id="header">
<h2><?php if (!empty($ADMIN)) {
  print "<a href='/admin/index.php'>Admin</a> &gt; ";
  print "<a href='/admin/list.php'>List Parents</a> &gt; ";
}
print href("/home.php");
?>Home</a> &gt; View and Edit Schedule</h2>
</div>
<div class="colmask fullpage">
<div class="col1">
<style>
#select { border-collapse: collapse; border: 0px solid black }
tr:nth-child(even) { background: #CCCCCC }
tr:nth-child(odd) { background: #FFFFFF }
tr.cash { font-style: italic }
tr.setup { }
tr.sales { font-weight: bold }
td, th { padding: 2px 2px 2px 2px; }
td.box { text-align: center }
</style>

<?php

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
  error_log("Error connecting to database");
}

$ENABLED = false;
$query = "SELECT * FROM options WHERE opt_start < NOW()";
if (($result = $mysqli->query($query)) !== FALSE) {
  while ($row = $result->fetch_array()) {
    $ENABLED = true;
  }
  $result->close();
}
if (!empty($ADMIN)) {
  $ENABLED = true;
  unset($ADD_ONLY);
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
  error_log("Error retrieving shifts");;
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

$LIMITS = analyze();
if (isset($SCOUT)) {
  $num_scouts = count($SCOUT);
} else {
  $num_scouts = 0;
}

$errors = "";

if (isset($_POST['cancel'])) {
  print "<script>window.location = '";
  redirect("/home.php");
  print "';</script>\n";
}
if (isset($_POST['submit'])) {
    if (!$ENABLED) {
        print "<script>window.location = '";
        redirect("/home.php");
        print "';</script>\n";
	exit;
    }
    // save post params
    foreach ($_POST as $key => $value) {
        // [1] = shift, [2] = scout
        if (preg_match("/^s(\d+)_(\d+)$/", $key, $matches)) {
            $shiftid = $matches[1];
            $scoutid = $matches[2];
            $scout_shifts[$scoutid][$shiftid] = 1;
        }
        if (preg_match("/^p(\d+)_(\d+)$/", $key, $matches)) {
            $shiftid = $matches[1];
            $parent_shifts[$shiftid] = 1;
        }
        if (preg_match("/^snow(\d+)$/", $key, $matches)) {
            $shiftid = $matches[1];
            $snow_shifts[$shiftid] = 1;
        }
    }

    //print "<pre>\n";
    //print_r($_POST);
    //print "</pre>\n";

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
    // SUCCESSFUL LOCKING

    $num_scouts = count($SCOUT);

    $id = $PARENT['id'];
    $query = "SELECT * FROM parent_shifts WHERE parentid = '$id'";
    if (($result = $mysqli->query($query)) !== FALSE) {
        while ($row = $result->fetch_array()) {
            $parent_shifts_in_db[$row['shiftid']] = 1;
        }
        $result->close();
    }

    $query = "SELECT * FROM snow_shifts WHERE parentid = '$id'";
    if (($result = $mysqli->query($query)) !== FALSE) {
        while ($row = $result->fetch_array()) {
            $snow_shifts_in_db[$row['shiftid']] = 1;
        }
        $result->close();
    }

    foreach ($SCOUT as $scoutid => $scout) {
        $query = "SELECT * FROM scout_shifts WHERE scoutid = '$scoutid'";
        if (($result = $mysqli->query($query)) !== FALSE) {
            while ($row = $result->fetch_array()) {
                $scout_shifts_in_db[$row['scoutid']][$row['shiftid']] = 1;
            }
            $result->close();
        }
    }

    // $parent_shifts_in_db are the shifts the parent currently has
    // $parent_shifts are the shifts the parent wants
    // $scout_shifts_in_db are the shifts the scouts currently have
    // $scout_shifts are the shifts the scouts want
    // $snow_shifts_in_db are the snow shifts the families currently have
    // $snow_shifts are the snow shifts the familite want

    // STEP 1: CHECK IF DESIRED SHIFTS ARE AVAILABLE

    foreach ($shifts as $shiftid => $shift) {
        if (!isset($parent_shifts_in_db[$shiftid]) &&
            isset($parent_shifts[$shiftid])) {
            // not in db, and is checked
            $adults = $shift['adults'];
            $query = "SELECT COUNT(*) FROM parent_shifts WHERE shiftid = '$shiftid'";
            if (($result = $mysqli->query($query)) !== FALSE) {
                $row = $result->fetch_row();
                $adults -= $row[0];
                $result->close();
            }
            if ($adults < 1) {
	      $start_str = strftime("%a, %b %e, %l:%M%P", $shift['start-time']);
	      $errors .= "Shift $shiftid ($start_err) is unavailable for the parent.<br/>";
	      error_log("Shift $shiftid ($start_err) is unavailable for the parent.");
            }
        }

        $scouts_in_shift = 0;
        foreach ($SCOUT as $scoutid => $scout) {
            if (!isset($scout_shifts_in_db[$scoutid][$shiftid]) &&
                isset($scout_shifts[$scoutid][$shiftid])) {
                // not in db, and is checked
                $scouts_in_shift++;
            }
        }
        if ($scouts_in_shift > 0) {
            $scouts = $shift['scouts'];
            $query = "SELECT COUNT(*) FROM scout_shifts WHERE shiftid = '$shiftid'";
            if (($result = $mysqli->query($query)) !== FALSE) {
                $row = $result->fetch_row();
                $scouts -= $row[0];
                $result->close();
            }
            if ($scouts < $scouts_in_shift) {
	      $start_str = strftime("%a, %b %e, %l:%M%P", $shift['start-time']);
	      $errors .= "Shift $shiftid ($start_str) is unavailable for your scouts.<br/>";
	      error_log("Shift $shiftid ($start_str) is unavailable for your scouts.");
            }
        }

        if (!isset($snow_shifts_in_db[$shiftid]) &&
            isset($snow_shifts[$shiftid])) {
            // not in db, and is checked
	    $families = $LIMITS['fps'];
            $query = "SELECT COUNT(*) FROM snow_shifts WHERE shiftid = '$shiftid'";
            if (($result = $mysqli->query($query)) !== FALSE) {
                $row = $result->fetch_row();
                $families -= $row[0];
                $result->close();
            }
            if ($families < 1) {
	      $start_str = strftime("%a, %b %e, %l:%M%P", $shift['start-time']);
              $errors .= "Shift $shiftid ($start_str) is unavailable for snow removal.<br/>";
	      error_log("Shift $shiftid ($start_str) is unavailable for snow removal.");
            }
        }
    }

    if (empty($errors)) {
        // NO ERRORS... update database and redirect
        // print "<p>NO ERRORS!</p>\n";
	if (isset($scout_shifts)) {
	  foreach ($scout_shifts as $scoutid => $value) {
            foreach ($value as $shiftid => $one) {
	      if (!isset($scout_shifts_in_db[$scoutid][$shiftid])) {
		$query = "INSERT INTO scout_shifts (shiftid, scoutid, troop) VALUES ($shiftid, $scoutid, $TROOP);";
		//print "QUERY: $query<br>\n";
		if ($mysqli->query($query) == FALSE) {
		  $errors .= "INSERT scout_shifts failed<br>";
		  error_log("INSERT scout_shifts failed: $query");
		}
	      }
            }
	  }
	}
        if (isset($scout_shifts_in_db)) {
            foreach ($scout_shifts_in_db as $scoutid => $value) {
                foreach ($value as $shiftid => $one) {
                    if (!isset($scout_shifts[$scoutid][$shiftid])) {
			$query = "DELETE FROM scout_shifts WHERE shiftid=$shiftid AND scoutid=$scoutid;";
			//print "QUERY: $query<br>\n";
			if ($mysqli->query($query) == FALSE) {
			  $errors .= "DELETE scout_shifts failed<br>";
			  error_log("DELETE scout_shifts failed: $query");
			}
                    }
                }
            }
        }
        $parentid = $PARENT['id'];
	if (isset($parent_shifts)) {
	  foreach ($parent_shifts as $shiftid => $one) {
            if (!isset($parent_shifts_in_db[$shiftid])) {
	      $query = "INSERT INTO parent_shifts (shiftid, parentid, troop) VALUES ($shiftid, $parentid, $TROOP);";
	      //print "QUERY: $query<br>\n";
	      if ($mysqli->query($query) == FALSE) {
		$errors .= "INSERT parent_shifts failed<br>";
		error_log("INSERT parent_shifts failed: $query");
	      }
            }
	  }
	}
        if (isset($parent_shifts_in_db)) {
            foreach ($parent_shifts_in_db as $shiftid => $one) {
                if (!isset($parent_shifts[$shiftid])) {
		    $query = "DELETE FROM parent_shifts WHERE shiftid=$shiftid AND parentid=$parentid;";
		    //print "QUERY: $query<br>\n";
		    if ($mysqli->query($query) == FALSE) {
		      $errors .= "DELETE parent_shifts failed<br>";
		      error_log("DELETE parent_shifts failed: $query");
		    }
                }
            }
        }

	if (isset($snow_shifts)) {
	  foreach ($snow_shifts as $shiftid => $one) {
            if (!isset($snow_shifts_in_db[$shiftid])) {
	      $query = "INSERT INTO snow_shifts (shiftid, parentid, troop) VALUES ($shiftid, $parentid, $TROOP);";
	      //print "QUERY: $query<br>\n";
	      if ($mysqli->query($query) == FALSE) {
		$errors .= "INSERT snow_shifts failed<br>";
		error_log("INSERT snow_shifts failed: $query");
	      }
            }
	  }
	}
        if (isset($snow_shifts_in_db)) {
            foreach ($snow_shifts_in_db as $shiftid => $one) {
                if (!isset($snow_shifts[$shiftid])) {
		    $query = "DELETE FROM snow_shifts WHERE shiftid=$shiftid AND parentid=$parentid;";
		    //print "QUERY: $query<br>\n";
		    if ($mysqli->query($query) == FALSE) {
		      $errors .= "DELETE snow_shifts failed<br>";
		      error_log("DELETE snow_shifts failed: $query");
		    }
                }
            }
        }
    }

    // else errors!!!!!!

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

    if (!empty($errors)) {
        print "<p><b>$errors</b></p>\n";
    } else {
      if (isset($AUTO_CONTINUE)) {
	print "<script>window.location = '";
	redirect("/home.php");
	print "';</script>\n";
      }
    }
}
if ($ENABLED) {
  print "<p>Please place a checkbox next to the shift(s) you want. ";
  if (isset($ADD_ONLY)) {
    print "<b>Removing shifts is not available at this time.</b> ";
    print "If you need to remove a shift, please add a shift first, then <a href='mailto:troop60trees@gmail.com'>email me</a>.";
  }
  print "</p>\n";
} else {
  print "<p>Tree Scheduling starts <b>$START</b>.</p>";
}


print '<form method ="post" ';
action("/select.php");
print ">\n";

$shifts_printed = 0;
$shifts_editable = 0;

// Get names, current shifts, limits
// parent is already in $PARENT
// scouts are already in $SCOUT[]

// if $scout_shifts and $parent_shifts are empty (because we didn't POST), then read
// the shifts from the database, if there was an error from above, these should be
// non-zero, so we *should* only read them when first coming to this form
if (!isset($parent_shifts)) {
    $id = $PARENT['id'];
    $query = "SELECT * FROM parent_shifts WHERE parentid = $id";
    if (($result = $mysqli->query($query)) !== FALSE) {
        while ($row = $result->fetch_array()) {
            $parent_shifts[$row['shiftid']] = 1;
        }
        $result->close();
    }
}
if (!isset($snow_shifts)) {
    $query = "SELECT * FROM snow_shifts WHERE parentid = $id";
    if (($result = $mysqli->query($query)) !== FALSE) {
        while ($row = $result->fetch_array()) {
            $snow_shifts[$row['shiftid']] = 1;
        }
        $result->close();
    }
}
$num_scouts = 0;
if (!isset($scout_shifts) && isset($SCOUT)) {
    foreach ($SCOUT as $key => $value) {
        $num_scouts++;
        $id = $value['id'];
        $query = "SELECT * FROM scout_shifts WHERE scoutid = $id";
        if (($result = $mysqli->query($query)) !== FALSE) {
            while ($row = $result->fetch_array()) {
                $scout_shifts[$row['scoutid']][$row['shiftid']] = 1;
            }
            $result->close();
        }
    }
}
print "<p>Scouts are to fill " . $LIMITS['sps'] . " shifts (minimum), parents are to fill " . $LIMITS['spp'] . " shifts (minimum).<br/>";
if ($LIMITS['min'] > 0) {
    print "Your family must fill " . ($LIMITS['min'] * ($num_scouts + 1)) . " shifts total (a parent or scout may take the extra shift).<br/>";
}
print "Each family must select 1 snow removal shift.</p>\n";
print "<table id='select'>\n";
print "<tr>";
print "<th>Parent<br/>(" . $LIMITS['spp'] . ")</th>";
if (isset($SCOUT)) {
  foreach ($SCOUT as $key2 => $value2) {
    print "<th>" . $value2['fname'] . "<br/>(" . $LIMITS['sps'] . ")</th>";
  }
}
print "<th>Snow<br/>Removal</th><th style='vertical-align: bottom'>Date/Time</th><th style='vertical-align:bottom'>Description</th>";
print "</tr>\n";

foreach ($shifts as $shiftid => $shift) {
  $adults = $shift['adults'];
  $scouts = $shift['scouts'];
  $snows = $LIMITS['fps'];
  $query = "SELECT COUNT(*) FROM parent_shifts WHERE shiftid = '$shiftid'";
  if (($result = $mysqli->query($query)) !== FALSE) {
    $row = $result->fetch_row();
    $adults -= $row[0];
    $result->close();
  }
  $query = "SELECT COUNT(*) FROM scout_shifts WHERE shiftid = '$shiftid'";
  if (($result = $mysqli->query($query)) !== FALSE) {
    $row = $result->fetch_row();
    $scouts -= $row[0];
    $result->close();
  }

  $query = "SELECT COUNT(*) FROM snow_shifts WHERE shiftid = '$shiftid'";
  if (($result = $mysqli->query($query)) !== FALSE) {
    $row = $result->fetch_row();
    $snows -= $row[0];
    $result->close();
  }

  $adult_is_full = false;
  if ($adults <= 0) {
    $adult_is_full = true;
  }
  $scout_is_full = false;
  if ($scouts <= 0) {
    $scout_is_full = true;
  }

  $snow_is_full = false;
  if ($snows <= 0) {
    $snow_is_full = true;
  }

  $is_past = false;
  if ($now >= $shift['start-time']) {
    $is_past = true;
  }

  // true if we want to print the line
  $is_interesting = false;
  $is_editable = false;

  $start = localtime($shift['start-time'], true);
  $end = localtime($shift['end-time'], true);

  // take the date, subtract the day number to determine the start of the week

  $is_cash = false;
  $is_snow = false;

  $end_str = "";
  if ($shift['type'] == $SHIFT_CASH_OPEN) {
    $start_str = strftime("%a, %b %e, %l:%M%P", $shift['start-time']);
    $is_cash = true;
  } else if ($shift['type'] == $SHIFT_CASH_CLOSE) {
    $start_str = strftime("%a, %b %e, %l:%M%P", $shift['start-time']);
    $is_cash = true;
  } else {
    $start_str = strftime("%a, %b %e, %l:%M%P-", $shift['start-time']);
    $end_str = trim(strftime("%l:%M%P", $shift['end-time'])); // trim any leading %l space
  }
  if ($shift['description'] == "Open Sales") {
    $is_snow = true;
  }

  // determine how to start the line
  if ($shift['type'] == $SHIFT_CASH_CLOSE ||
      $shift['type'] == $SHIFT_CASH_OPEN) {
    $line = "<tr class='cash'>";
  } else if ($shift['type'] == $SHIFT_SETUP) {
    $line = "<tr class='setup'>";
  } else {
    $line = "<tr class='sales'>";
  }

  // PARENT CELL
  $variable = "p" . $shiftid . "_" . $PARENT['id'];
  $line .= "<td class='box'>";
  if ($ENABLED) {
    if ($is_past) {
      if (isset($parent_shifts[$shiftid])) {
        $is_interesting = true;
        $line .= "$X<input type='hidden' value='1' name='$variable' />";
      } else {
        // else don't put anything in the cell
        $line .= "&nbsp;";
      }
    } else {
      if (isset($parent_shifts[$shiftid])) {
        $is_interesting = true;
        if (isset($ADD_ONLY) && empty($errors)) {
	  $line .= "$X<input type='hidden' value='1' name='$variable' />";
        } else {
	  $is_editable = true;
	  $line .= "<input type='checkbox' checked='checked' name='$variable' />";
        }
      } else if ($adult_is_full) {
        $line .= "FULL";
      } else {
        $is_interesting = true;
        $is_editable = true;
        $line .= "<input type='checkbox' name='$variable' />";
      }
    }
  } else {
    $is_interesting = true;
  }
  $line .= "</td>";

  // SCOUT CELLS
  if (isset($SCOUT)) {
    foreach ($SCOUT as $scoutid => $scout) {
      $variable = "s" . $shiftid . "_" . $scoutid;
      $line .= "<td class='box'>";
      if ($ENABLED) {
        if ($is_cash) {
          // scouts can't do cash shifts
          $line .= "&nbsp;";
        } else if ($is_past) {
          if (isset($scout_shifts[$scoutid][$shiftid])) {
	    $is_interesting = true;
	    $line .= "$X<input type='hidden' value='1' name='$variable' />";
          } else {
  	    // else don't put anything in the cell
  	    $line .= "&nbsp;";
          }
        } else {
          if (isset($scout_shifts[$scoutid][$shiftid])) {
	    $is_interesting = true;
	    if (isset($ADD_ONLY) && empty($errors)) {
	      $line .= "$X<input type='hidden' value='1' name='$variable' />";
	    } else {
	      $is_editable = true;
	      $line .= "<input type='checkbox' checked='checked' name='$variable' />";
	    }
          } else if ($scout_is_full) {
	    $line .= "FULL";
          } else {
	    $is_interesting = true;
	    $is_editable = true;
	    $line .= "<input type='checkbox' name='$variable' />";
          }
        }
      }
      $line .= "</td>";
    }
  }

  // SNOW CELL
  $variable = "snow" . $shiftid;
  $line .= "<td class='box'>";
  if ($ENABLED) {
    if ($is_past) {
      if (isset($snow_shifts[$shiftid])) {
        $is_interesting = true;
        $line .= "$X<input type='hidden' value='1' name='$variable' />";
      } else {
        // else don't put anything in the cell
        $line .= "&nbsp;";
      }
    } else if ($is_snow) {
      if (isset($snow_shifts[$shiftid])) {
        $is_interesting = true;
        if (isset($ADD_ONLY) && empty($errors)) {
	  $line .= "$X<input type='hidden' value='1' name='$variable' />";
        } else {
	  $is_editable = true;
	  $line .= "<input type='checkbox' checked='checked' name='$variable' />";
        }
      } else if ($snow_is_full) {
        $line .= "FULL";
      } else {
        $is_interesting = true;
        $is_editable = true;
        $line .= "<input type='checkbox' name='$variable' />";
      }
    } else {
      // else don't put anything in the cell
      $line .= "&nbsp;";
    }
  }
  $line .= "</td>";

  $line .= "<td>" . $start_str . $end_str . "</td>";
  $line .= "<td>" . $shift['description'];
  if ($is_snow) {
    $line .= " (snow removal)";
  }
  if ($shift['type'] == $SHIFT_CASH_OPEN ||
      $shift['type'] == $SHIFT_CASH_CLOSE) {
    $line .= " </i>-- counts as 1/$CASH_VALUE of a shift</i>";
  }
  if ($scouts > 0 && $scouts < $num_scouts) {
    $line .= " </b> -- Only $scouts space";
    if ($scouts > 1) {
      $line .= "s";
    }
    $line .= " available for scouts.</b>";
  }
  $line .= "</td></tr>\n";

  if ($is_interesting) {
    if ($is_editable) {
      $shifts_editable++;
    }
    $shifts_printed++;
    print $line;
  }
}
print "</table>\n";
if ($ENABLED) {
  if ($shifts_editable > 0) {
    print "<input type='submit' name='submit' value='Submit'>\n";
    print "<input type='submit' name='cancel' value='Cancel'>\n";
  } else {
    print "<p>No shifts available!</p>\n";
  }
  if ($shifts_printed == 0) {
     print "<style>\nth { visibility: hidden }\n</style>\n";
  }
}
?>
</form>
</div>
</div>
</body>
</html>
