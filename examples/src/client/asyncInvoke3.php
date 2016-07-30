<?php
require_once "../../vendor/autoload.php";

use Hprose\Client;
use Hprose\Future;

$client = Client::create('http://hprose.com/example/');

$var_dump = Future\wrap('var_dump');
$sum = $client->sum;

$r1 = $sum(1, 3, 5, 7, 9);
$r2 = $sum(2, 4, 6, 8, 10);
$r3 = $sum($r1, $r2);
$var_dump($r1, $r2, $r3);
