<?php
include "/var/www/walters-short/html/trees/mysql.php";
include "/var/www/walters-short/html/trees/shifttypes.php";
include "/var/www/walters-short/html/trees/analyze.php";
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

foreach ($parents as $parentid => $parent) {
   $complete = true;
   $total_shifts = 0;
   $total_people = 1; // the parent
   $snow_state = "<li style='color:green'>OK</li>";
   $parent_state = "<li style='color:green'>OK</li>";
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
     $parent_state = "<li style='color:red'>Parent needs $need shift(s)</li>\n";
     $complete = false;
   }
   if (!$has_snow) {
     $snow_state = "<li style='color:red'>Family needs snow removal shift</li>\n";
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
       $scout_state .= "<li style='color:red'>$name needs $need shift(s)</li>\n";
       $complete = false;
     }
     $total_shifts += $numshifts;
     $total_people++;
   }

   if ($LIMITS['min'] > 0) {
     $need = $LIMITS['min'] * $total_people;
     if ($total_shifts < $need) {
       $total_state = "<li style='color:red'>Family needs " . ($need - $total_shifts) . " shift(s)</li>";
       $complete = false;
     } else {
       $total_state = "<li style='color:green'>OK</li>";
     }
   } else {
     $total_state = "";
   }

   if ($parent['do_not_nag'] != 0) {
     $complete = true;
   }

   if (!$complete) {
     if (empty($scout_state)) {
       $scout_state = "<li style='color:green'>OK</li>";
     }
     
     // Send the nag email to everyone
     $query = "SELECT email FROM emails WHERE pemail = '" . $parent['email'] . "';";
     if (($result = $mysqli->query($query)) !== FALSE) {
       while ($arr = $result->fetch_array()) {
	 send_the_email($parent['pname'], $parent['password'], $arr['email'], 
			"https://www.treesale.christmas",
			$snow_state, $parent_state, $scout_state, $total_state);
       }
       $result->close();
     }
   }
}

function send_the_email($pname, $password, $email, $dirname, $snow_state, $parent_state, $scout_state, $total_state)
{
  global $START;
  $stats = analyze();

  $subject = "Please complete your Tree Scheduling";

  $myemail = 'troop60trees@gmail.com';

  $boundary = "TreesTreesBeautifulTrees";

  $headers  = 'From: Todd Short <troop60trees@gmail.com>' . "\r\n";
  $headers .= 'Bcc: todd.short@me.com' . "\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: multipart/alternative;boundary=$boundary\r\n";

  $message  = "This is a multi-part message in MIME format.\n";
  $message .= "\r\n\r\n--$boundary\r\n";
  $message .= "Content-Type: text/plain;charset=utf-8\r\n\r\n";

  $message .= "Plain text - read the HTML-encoded email!!\n";

  $message .= "\r\n\r\n--$boundary\r\n";
  $message .= "Content-Type: text/html;charset=utf-8\r\n\r\n";

  $message .= "<p>Hello $pname,</p>\n\n";

  $message .= "<p style='font-weight:bold'>This is a reminder that you still need to complete your schedule for the annual Boy Scout Tree Sale.</p>\n";

  $message .= "<p><b>Requirements:</b></p>\n";
  
  $message .= "<ol>\n";
  $message .= "<li>Each <i>family</i> is required to work (" . $stats['spp'] . ") adult shifts (minimum). Either parent may work this shift.</li>\n";
  $message .= "<ul>$parent_state</ul>\n";
  $message .= "<li>Each <i>scout</i> is required to work (" . $stats['sps'] . ") scout shifts (minimum).</li>\n";
  $message .= "<ul>$scout_state</ul>\n";
  if ($stats['min'] > 0) {
    $message .= "<li>Each <i>family</i> is required to work (" . $stats['min'] . ") shifts per person. A scout or a parent may fulfill these extra shifts.</li>\n";
    $message .= "<ul>$total_state</ul>\n";
  }
  $message .= "<li>Each <i>family</i> is required to sign up for an <b>On-Call Snow Removal</b> shift.</li>\n";
  $message .= "<ul>$snow_state</ul>\n";
  $message .= "</ol>\n";

  $message .= "<p><b>If you cannot fulfill your shift</b>, it is your responsibility to find a replacement or a trade. The website does not limit ";
  $message .= "the number of shifts you may sign up for; but you cannot remove a shift. If possible, please sign up for another shift. ";
  $message .= " If you need a shift traded, removed or modified; please contact <a href=\"mailto:$myemail\">me via email</a>.</p>\n";

  $message .= "<p><b><i>Here is the link you need to complete your Tree Sale scheduling.</i></b></p>\n";

  $link = $dirname . "/home.php/$password";
  
  $message .= "<p><a href=\"$link\">$link</a></p>\n";

  $message .= "<p><b>Note:</b></p>\n";
  $message .= "<ul>";
  $message .= "<li>There is no password to the site.</li>\n";
  $message .= "<li>The link above is unique for your family. Please do not share this link with anyone outside your family, as it will give them access to your schedule.</li>\n";
  $message .= "<li>Your schedule is <b>not</b> emailed to you; it is available at the link above.</li>\n";
  $message .= "<li>The website does not enforce any limitations on the number of shifts you choose. If you choose too few or too many, the website will tell you.</li>\n";
  $message .= "<li>If you do not sign up for enough shifts, you will get nag emails until you do. <i>This is a nag email.</i></li>\n";
  $message .= "</ul>\n";

  $message .= "<p>If you have any questions, please don't hesitate to email me.</p>\n";
  
  $message .= "<p>Thank you,</p>\n<p>Todd Short<br/><a href=\"mailto:$myemail\">$myemail</a></p>\n";
  $message .= "<p>This email was sent to $email</p>\n";
  $message .= "\r\n\r\n--$boundary--\r\n\r\n";

  $toemail = $email;
  //$toemail = "todd.short@me.com"; // temp

  $ret = mail($toemail, $subject, $message, $headers);
  if ($ret === FALSE) {
    print "Unable to send nag email to $pname! ($email)\n";
  } else {
    print "Sent nag email to $pname ($email)\n";
  }
}

?>
