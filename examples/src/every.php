<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$dump = Future\wrap('var_dump');

function isBigEnough($value) {
  return $value >= 10;
}

$a1 = array(12, Future\value(5), 8, Future\value(130), 44);
$a2 = array(12, Future\value(54), 18, Future\value(130), 44);
$a3 = Future\value($a1);
$a4 = Future\value($a2);
$dump(Future\every($a1, 'isBigEnough'));   // false
$dump(Future\every($a2, 'isBigEnough'));   // true
$dump(Future\every($a3, 'isBigEnough'));   // false
$dump(Future\every($a4, 'isBigEnough'));   // true
