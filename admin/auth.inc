<?php
require_once "../config.inc";

if (!isset($_SERVER['PHP_AUTH_USER'])) {
  header('WWW-Authenticate: Basic realm="Trees"');
  header('HTTP/1.0 401 Unauthorized');
  echo '401 Unauthorized';
  exit;
}
$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];
if ($user != $ADMINUSER || $pass != $ADMINPASS) {
  header('WWW-Authenticate: Basic realm="Trees"');
  header('HTTP/1.0 401 Unauthorized');
  echo '401 Unauthorized';
  exit;
}
?>