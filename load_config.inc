<?php

$root = $_SERVER['DOCUMENT_ROOT'];
if (empty($root))
   $root = dirname(__FILE__);
$cfgfile = $root . '/config.inc';
$to_eval = file_get_contents($cfgfile);

$ret = eval($to_eval);

if (!$ret) {
   exit("Error reading config.inc\n");
}

?>