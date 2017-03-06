<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$dump = Future\wrap('var_dump');

$numbers = Future\value(array(Future\value(0), 1, Future\value(2), 3, Future\value(4)));

function add($a, $b) {
  return $a + $b;
}

$dump($numbers->reduce('add'));
$dump($numbers->reduce('add', 10));
$dump($numbers->reduce('add', Future\value(20)));
