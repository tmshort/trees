<?php
include "auth.inc";
include "../mysql.php";
?><html>
<head>
<title>Reset Database</title>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<link href="../trees.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="colmask fullpage">
<div class="col1">
<h2><a href="index.php">Admin</a> &gt; <a href="database.php">Database</a> &gt; Reset</h2>
<?php

if ($_POST["force"]) {
  $mysqli = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
  print "<b>" . $mysqli->error . "</b>";
  if ($mysqli->connect_errno) {
    print "<b>Failed to connect to MySQL: " . $mysqli->connect_error . "</b>\n";
    exit;
  }
  
  if ($mysqli->query("DROP DATABASE IF EXISTS trees") === FALSE) {
    print "<b>Failed to delete existing database " . $mysqli->error . "</b>\n";
    exit;
  }

  if ($mysqli->query("CREATE DATABASE IF NOT EXISTS trees") === FALSE) {
    print "<b>Failed to create database " . $mysqli->error . "</b>\n";
    exit;
  }

  $mysqli->select_db("trees");

  if ($result = $mysqli->query("SELECT DATABASE()")) {
    $row = $result->fetch_row();
    print "Database: <b>" . $row[0] . "</b><br/>";
    $result->close();
  } else {
    print "<b>" . $mysqli->error . "</b>";
  }

  if ($mysqli->query("CREATE TABLE scouts (id MEDIUMINT NOT NULL AUTO_INCREMENT, sname VARCHAR(64) NOT NULL, fname VARCHAR(64) NOT NULL, pname VARCHAR(64) NOT NULL, email VARCHAR(64) NOT NULL, PRIMARY KEY (id));") === FALSE) {
    print "<b>Failed to create table scouts " . $mysqli->error . "</b>\n";
    exit;
  }

  if ($mysqli->query("CREATE TABLE parents (id MEDIUMINT NOT NULL AUTO_INCREMENT, pname VARCHAR(64), password VARCHAR(64), email VARCHAR(64), do_not_nag TINYINT DEFAULT 0 NOT NULL, PRIMARY KEY (id));") === FALSE) {
    print "<b>Failed to create table parents " . $mysqli->error . "</b>\n";
    exit;
  }

  if ($mysqli->query("CREATE TABLE emails (id MEDIUMINT NOT NULL AUTO_INCREMENT, pemail VARCHAR(64), email VARCHAR(64), PRIMARY KEY (id));") === FALSE) {
    print "<b>Failed to create table emails " . $mysqli->error . "</b>\n";
    exit;
  }

  if ($mysqli->query("CREATE TABLE shifts (id MEDIUMINT NOT NULL, start DATETIME, end DATETIME, description VARCHAR(64), scouts SMALLINT, adults SMALLINT, type TINYINT, PRIMARY KEY(id));") === FALSE) {
    print "<b>Failed to create table shifts " . $mysqli->error . "</b>\n";
    exit;
  }

  if ($mysqli->query("CREATE TABLE scout_shifts (shiftid MEDIUMINT NOT NULL, scoutid MEDIUMINT NOT NULL);") === FALSE) {
    print "<b>Failed to create table parent_shifts " . $mysqli->error . "</b>\n";
    exit;
  }

  if ($mysqli->query("CREATE TABLE parent_shifts (shiftid MEDIUMINT NOT NULL, parentid MEDIUMINT NOT NULL);") === FALSE) {
    print "<b>Failed to create table parent_shifts " . $mysqli->error . "</b>\n";
    exit;
  }

  if ($mysqli->query("CREATE TABLE snow_shifts (shiftid MEDIUMINT NOT NULL, parentid MEDIUMINT NOT NULL);") === FALSE) {
    print "<b>Failed to create table snow_shifts " . $mysqli->error . "</b>\n";
    exit;
  }

  if ($mysqli->query("CREATE TABLE options (unused_index MEDIUMINT NOT NULL AUTO_INCREMENT, opt_continue TINYINT NOT NULL, opt_addonly TINYINT NOT NULL, opt_disabled TINYINT NOT NULL, opt_cash TINYINT NOT NULL, opt_nag TINYINT NOT NULL, opt_start DATETIME NOT NULL, opt_min TINYINT NOT NULL, opt_min_p TINYINT NOT NULL, opt_min_s TINYINT NOT NULL, opt_name VARCHAR(64), opt_email VARCHAR(64), PRIMARY KEY(unused_index));") === FALSE) {
    print "<b>Failed to create table options " . $mysqli->error . "</b>\n";
    exit;
  }

  if ($mysqli->query("INSERT INTO options (opt_continue, opt_addonly, opt_disabled, opt_cash, opt_nag, opt_start, opt_min, opt_min_p, opt_min_s, opt_name, opt_email) VALUES (1, 0, 1, 3, 1, '2020-01-01 10:00:00', 0, 0, 0, '$DEFNAME', '$DEFEMAIL');") === FALSE) {
    print "<b>Failed to create table options " . $mysqli->error . "</b>\n";
    exit;
  }

  print "<b>Database created!</b>\n";
  
  $mysqli->close();
} else {
  print "Nothing done!";
}
?>
<a href="database.php">Back to database page</a>
</div>
</div>
</body>
</html>



