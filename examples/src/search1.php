<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$dump = Future\wrap('var_dump');

$numbers = array(Future\value(0), 1, Future\value(2), 3, Future\value(4));

$dump(Future\search($numbers, 2));
$dump(Future\search($numbers, Future\value(3)));
$dump(Future\search($numbers, true));
$dump(Future\search($numbers, true, true));
