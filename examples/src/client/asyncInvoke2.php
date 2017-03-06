<?php
require_once "../../vendor/autoload.php";

use Hprose\Client;
use Hprose\Future;

$client = Client::create('http://hprose.com/example/');

$var_dump = Future\wrap('var_dump');
$sum = $client->sum;

$var_dump($sum($sum($sum(1, 2), 3), 4));