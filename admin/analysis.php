<?php
include "auth.inc";
include "../mysql.php";
include "../analyze.php";
?><html>
<head>
<title>Tree Scheduling Analysis</title>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<link href="../trees.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="colmask fullpage">
<div class="col1">
<h2><a href="index.php">Admin</a> &gt; Schedule Analysis</h2>
<?php

$arr = analyze();
print "Parents: " . $arr['parents'] . ", shifts: " . $arr['parent_shifts'];
print " =&gt; " . $arr['shifts_per_parent'] . " =&gt; " . $arr['spp'];
print " (slop " . (($arr['spp'] * $arr['parents']) - $arr['parent_shifts']) . ")";
print " (" . ($arr['parent_shifts'] / $arr['spp']). " parents to fill)";
print " override = " . $arr['min_p'] . "<br/>\n";
print "Scouts: " . $arr['scouts'] . ", shifts: " . $arr['scout_shifts'];
print " =&gt; " . $arr['shifts_per_scout'] . " =&gt; " . $arr['sps'];
print " (slop " . (($arr['sps'] * $arr['scouts']) - $arr['scout_shifts']) . ")";
print " (" . ($arr['scout_shifts'] / $arr['sps']). " scouts to fill)";
print " override = " . $arr['min_s'] . "<br/>\n";

$people = $arr['scouts'] + $arr['parents'];
$shifts = $arr['scout_shifts'] + $arr['parent_shifts'];
$shifts_per_person = $shifts / $people;
$spp = ceil($shifts_per_person);

print "Everyone: $people, shifts: $shifts";
print " =&gt; " . $shifts_per_person . " =&gt; " . $spp;
print " (slop " . (($spp * $people) - $shifts) . ")";
print " (" . ($shifts / $spp). " people to fill)";
print " override = " . $arr['min'] . "<br/>\n";

print "<pre>\n";
print_r($arr);
print "</pre>\n";
?>
</div>
</div>
</body>
</html>



