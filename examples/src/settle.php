<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$p1 = Future\resolve(3);
$p2 = Future\reject(new Exception("x"));

Future\settle(array(true, $p1, $p2))->then('print_r');

