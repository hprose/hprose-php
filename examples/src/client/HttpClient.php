<?php
require_once "../../../vendor/autoload.php";

use Hprose\Client;

$client = Client::create('http://127.0.0.1:8080/', false);
var_dump($client->hello("world"));
var_dump($client->sum(1, 2, 3));
