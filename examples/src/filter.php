<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$dump = Future\wrap('var_dump');

function isBigEnough($value) {
  return $value >= 8;
}

$a1 = array(12, Future\value(5), 8, Future\value(130), 44);
$a2 = Future\value($a1);
$dump(Future\filter($a1, 'isBigEnough'));
$dump(Future\filter($a2, 'isBigEnough'));

$a3 = array('Tom' => 8, 'Jerry' => Future\value(5), 'Spike' => 10, 'Tyke' => 3);
$a4 = Future\value($a3);
$dump(Future\filter($a3, 'isBigEnough'));
$dump(Future\filter($a3, 'isBigEnough', true));
$dump(Future\filter($a4, 'isBigEnough', true));
