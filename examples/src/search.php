<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$dump = Future\wrap('var_dump');

$numbers = Future\value(array(Future\value(0), 1, Future\value(2), 3, Future\value(4)));

$dump($numbers->search(2));
$dump($numbers->search(Future\value(3)));
$dump($numbers->search(true));
$dump($numbers->search(true, true));
