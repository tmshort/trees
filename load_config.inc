<?php

$cfgfile = $_SERVER['DOCUMENT_ROOT'] . '/config.inc';
$to_eval = file_get_contents($cfgfile);

$ret = eval(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/config.inc'));

if (!$ret) {
   exit("Error reading config.inc");
}

?>