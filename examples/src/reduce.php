<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$dump = Future\wrap('var_dump');

$numbers = array(Future\value(0), 1, Future\value(2), 3, Future\value(4));

function add($a, $b) {
  return $a + $b;
}

$dump(Future\reduce($numbers, 'add'));
$dump(Future\reduce($numbers, 'add', 10));
$dump(Future\reduce($numbers, 'add', Future\value(20)));
