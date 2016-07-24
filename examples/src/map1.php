<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$dump = Future\wrap('var_dump');

$a = Future\value(array(1, Future\value(4), 9));
$dump($a->map('sqrt'));