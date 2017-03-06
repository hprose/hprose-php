<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$dump = Future\wrap('var_dump');

function isBigEnough($value) {
  return $value >= 8;
}

$a = Future\value(array(
    'Tom' => 8,
    'Jerry' => Future\value(5),
    'Spike' => 10,
    'Tyke' => 3
));

$dump($a->filter('isBigEnough'));
$dump($a->filter('isBigEnough', true));
