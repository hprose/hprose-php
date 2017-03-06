<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$dump = Future\wrap('var_dump');

$a = array(1, Future\value(4), 9);
$dump(Future\map($a, 'sqrt'));