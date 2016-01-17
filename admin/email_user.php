<?php
ini_set('sendmail_from', 'troop60trees@gmail.com');
date_default_timezone_set("America/New_York");

function send_the_email($pname, $password, $email, $dirname, $orig_email = "")
{
  global $START;
  $stats = analyze();
  /* if there's an updated email, use it */
  if (!empty($orig_email)) {
    $email = $orig_email;
  }

  $subject = "Here is the link to start Tree Scheduling";

  $myemail = 'troop60trees@gmail.com';

  $boundary = "TreesTreesBeautifulTrees";

  $headers  = 'From: Todd Short <troop60trees@gmail.com>' . "\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: multipart/alternative;boundary=$boundary\r\n";

  $message  = "This is a multi-part message in MIME format.\n";
  $message .= "\r\n\r\n--$boundary\r\n";
  $message .= "Content-Type: text/plain;charset=utf-8\r\n\r\n";

  $message .= "Plain text - read the HTML-encoded email!!\n";

  $message .= "\r\n\r\n--$boundary\r\n";
  $message .= "Content-Type: text/html;charset=utf-8\r\n\r\n";

  $message .= "<p>Hello $pname,</p>\n\n";

  $message .= "<p style='color:red;font-weight:bold;font-style:italic'>Please read this entire email, it contains important information about the Tree Sale.</p>\n";

  $message .= "<p>We are ready to organize volunteer shifts for the <b>Annual Boy Scout Holiday Tree Sale</b>. We have around 1055 trees to sell. ";
  $message .= "This is the ONLY fundraiser for our Troop. Participation of our scouts and their families is ";
  $message .= "expected and mandatory, with no exceptions. As it has been the custom for many years, all active members of the Troop, ";
  $message .= "including Eagle members, are required to serve the required number of shifts. Remember, <b><i>Tree Sales are fun!</i></b></p>\n";
  
  $message .= "<p>We will using the same online system as last year for scheduling tree sales. I have developed a website that is specifically designed for ";
  $message .= "Troop 60's sales schedules. Your <i>personalized</i> link is at the bottom of this email. Please do not email me asking for the schedule in email ";
  $message .= "or send your shift requests via email. Shift assignments fill up very quickly, so please go to the site shortly after it ";
  $message .= "goes live.</p>\n";

  $message .= "<p><b>Requirements:</b></p>\n";
  
  $message .= "<ol>";
  $message .= "<li>Each <i>family</i> is required to work (" . $stats['spp'] . ") adult shifts (minimum). Either parent may work these shifts.</li>";
  $message .= "<li>Each <i>scout</i> is required to work (" . $stats['sps'] . ") scout shifts (minimum).</li>";
  if ($stats['min'] > 0) {
    $message .= "<li>However, each <i>family</i> is required to work (" . $stats['min'] . ") shifts per person. A scout or a parent may fulfill these extra shifts.</li>";
  } 
  $message .= "<li>Each <i>family</i> is required to sign up for an <b>On-Call Snow Removal</b> shift.</li>";
  $message .= "</ol>";

  $message .= "<p>We have several kinds of shifts: corral setup, tree unloading/tagging, drilling trees (we need older scouts for this), ";
  $message .= "opening and closing of the tree site (a.k.a. Cash shifts, adults only), and corral breakdown.</p>\n";

  $message .= "<p><b>Opening and Closing Cash shifts:</b> This year, (" . $stats['cash_value'] . ") of the opening or closing shifts will count ";
  $message .= "as one (1) adult shift.</p>\n";
  
  $message .= "<ul><li><u>Opening</u> the tree site involves arriving 15 minutes before the first shift of the day and completing a checklist to set ";
  $message .= "up the site. You will also be required to bring the starting cash to the site.</li><li><u>Closing</u> the site at the end of the sales ";
  $message .= "day involves a checklist to secure the site and dropping off the money collected that day to the treasurer. You should ";
  $message .= "arrive 15 minutes before closing. The treasurer for the Tree Sale is Dan DiFelice, from our Troop. I will send separate ";
  $message .= "instructions for this.</li><li>There are only (" . $stats['cash_shifts'] . ") of these shifts.</li></ul>";

  $message .= "<p><b>On-Call Snow Removal shifts:</b> If there has been a big snow on one of the days our Troop has the first selling shift of the ";
  $message .= "day, our Troop is responsible for shoveling around the trees and the pathways around the site. The scouts and parents ";
  $message .= "scheduled to be <i>on-call</i> that day come to the site at the begining of the shift to clear snow and leave once the job is done. ";
  $message .= "Bring your shovel with your name on it! A suggestion is to choose a date when you are going to be on a sales shift.</p>\n";

  $message .= "<p><b>If you cannot fulfill your shift</b>, it is your responsibility to find a replacement or a trade. The website does not limit ";
  $message .= "the number of shifts you may sign up for; but you can only edit them for a short period of time. If possible, please sign up for another shift. ";
  $message .= " If you need a shift traded, removed or modified; please contact <a href=\"mailto:$myemail\">me via email</a>.</p>\n";

  $message .= "<p><b><i>Here is the link you need to start your Tree Sale scheduling.</i></b></p>\n";

  if ($dirname == "/") {
    $dirname = "";
  }
  $link = "https://" . $_SERVER['SERVER_NAME'] . $dirname . "/home.php/$password";
  
  $message .= "<p><a href=\"$link\">$link</a></p>\n";

  $message .= "<p><b>Note:</b></p>\n";
  $message .= "<ul>";
  $message .= "<li>There is no password to the site.</li>";
  $message .= "<li>The link above is unique for your family. Please do not share this link with anyone outside your family, as it will give them access to your schedule.</li>";
  $message .= "<li>Your schedule is <b>not</b> emailed to you; it is available at the link above.</li>";
  $message .= "<li>The website does not enforce any limitations on the number of shifts you choose. If you choose too few or too many, the website will tell you.</li>";
  $message .= "<li>If you do not sign up for enough shifts, you will get nag emails until you do.</li>";
  $message .= "</ul>";

  $message .= "<p>The website will go live on <b>$START</b>, just like Ticketmaster. You should review the available shifts before the site goes live.</p>\n";
  $message .= "<p>Although it will work on a phone, the site works best in a regular web browser.</p>\n";
 
  $message .= "<p>If you have any questions, please don't hesitate to email me.</p>\n";
  
  $message .= "<p>Thank you,</p>\n<p>Todd Short<br/><a href=\"mailto:$myemail\">$myemail</a></p>\n";
  $message .= "<p>This email was sent to $email</p>\n";
  $message .= "\r\n\r\n--$boundary--\r\n\r\n";

  //$email = "todd.short@me.com"; // TODO remove this!!

  $ret = mail($email, $subject, $message, $headers);
  if ($ret === FALSE) {
    print "Unable to send email!<br/>\n";
  }
}
?>

