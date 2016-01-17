<?php
function analyze()
{
  $arr['parents'] = 0;
  $arr['scouts'] = 0;
  $arr['parent_shifts_raw'] = 0;
  $arr['parent_shifts'] = 0;
  $arr['cash_shifts'] = 0;
  $arr['cash_value'] = 3; // default
  $arr['scout_shifts'] = 0;
  $arr['snow_shifts'] = 0;
  $arr['shifts_per_scout'] = 0;
  $arr['shifts_per_parent'] = 0;
  $arr['families_per_snow'] = 0;
  $arr['spp'] = 0;
  $arr['sps'] = 0;
  $arr['fps'] = 0;
  $arr['min'] = 0;
  $arr['min_p'] = 0;
  $arr['min_s'] = 0;

  $mysqli = db_connect();
  if ($mysqli === FALSE) {
    return FALSE;
  }
  $query = "SELECT COUNT(*) FROM parents";
  if (($result = $mysqli->query($query)) === FALSE) {
    return FALSE;
  }
  $row = $result->fetch_row();
  $arr['parents'] = 0 + $row[0];
  $result->close();

  $query = "SELECT COUNT(*) FROM scouts";
  if (($result = $mysqli->query($query)) === FALSE) {
    return FALSE;
  }
  $row = $result->fetch_row();
  $arr['scouts'] = 0 + $row[0];
  $result->close();

  $query = "SELECT SUM(scouts), SUM(adults) FROM shifts";
  if (($result = $mysqli->query($query)) === FALSE) {
    return FALSE;
  }
  $row = $result->fetch_row();
  $arr['scout_shifts'] = 0 + $row[0];
  $arr['parent_shifts'] = 0 + $row[1];
  $result->close();

  $query = "SELECT COUNT(*) FROM shifts WHERE id > 1000";
  if (($result = $mysqli->query($query)) === FALSE) {
    return FALSE;
  }
  $row = $result->fetch_row();
  $arr['cash_shifts'] = 0 + $row[0];
  $result->close();

  $query = "SELECT COUNT(*) FROM shifts WHERE description = 'Open Sales'";
  if (($result = $mysqli->query($query)) === FALSE) {
    return FALSE;
  }
  $row = $result->fetch_row();
  $arr['snow_shifts'] = 0 + $row[0];
  $result->close();

  $query = "SELECT * FROM options";
  if (($result = $mysqli->query($query)) !== FALSE) {
    while ($row = $result->fetch_array()) {
      $arr['cash_value'] = 0 + $row['opt_cash'];
      $arr['min'] = 0 + $row['opt_min'];
      $arr['min_p'] = 0 + $row['opt_min_p'];
      $arr['min_s'] = 0 + $row['opt_min_s'];
    }
  }

  // Adjust for cash shifts
  $arr['parent_shifts_raw'] = $arr['parent_shifts'];
  $arr['parent_shifts'] -= ($arr['cash_shifts'] * ($arr['cash_value'] - 1)) / $arr['cash_value'];

  if ($arr['scouts'] && $arr['scout_shifts']) {
    $arr['shifts_per_scout'] = $arr['scout_shifts'] / $arr['scouts'];
    $arr['sps'] = ceil($arr['shifts_per_scout']);
  }

  if ($arr['parents'] && $arr['parent_shifts']) {
    $arr['shifts_per_parent'] = $arr['parent_shifts'] / $arr['parents'];
    $arr['spp'] = ceil($arr['shifts_per_parent']);
  }

  if ($arr['parents'] && $arr['snow_shifts']) {
    $arr['families_per_snow'] = $arr['parents'] / $arr['snow_shifts'];
    $arr['fps'] = ceil($arr['families_per_snow']);
  }

  // overrides
  if ($arr['min_p'] > 0) {
    $arr['spp'] = 0 + $arr['min_p'];
  }
  if ($arr['min_s'] > 0) {
    $arr['sps'] = 0 + $arr['min_s'];
  }

  return $arr;
}
?>


