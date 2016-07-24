<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$dump = Future\wrap('var_dump');

function isBigEnough($value) {
  return $value >= 10;
}

$a1 = array(12, Future\value(5), 8, Future\value(130), 44);
$a2 = array(1, Future\value(5), 8, Future\value(1), 4);
$a3 = Future\value($a1);
$a4 = Future\value($a2);
$dump(Future\some($a1, 'isBigEnough'));   // true
$dump(Future\some($a2, 'isBigEnough'));   // false
$dump(Future\some($a3, 'isBigEnough'));   // true
$dump(Future\some($a4, 'isBigEnough'));   // false
