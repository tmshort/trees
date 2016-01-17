<?php

/* Password Generation function */

function generatePassword($length = 8)
{
  static $pwchars = 'bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ23456789';
  $count = mb_strlen($pwchars);
  $result = '';
  for ($i = 0; $i < $length; $i++) {
    $index = rand(0, $count - 1);
    $result .= mb_substr($pwchars, $index, 1);
  }
  return $result;
}



?>