<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$dump = Future\wrap('var_dump');

function isBigEnough($value) {
  return $value >= 10;
}

$a1 = Future\value(array(12, Future\value(5), 8, Future\value(130), 44));
$a2 = Future\value(array(1, Future\value(5), 8, Future\value(1), 4));
$dump($a1->some('isBigEnough'));   // true
$dump($a2->some('isBigEnough'));   // false
