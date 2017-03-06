<?php
require_once "../../../vendor/autoload.php";
require_once '../logHandler2.php';

use Hprose\Client;

$client = Client::create('tcp://127.0.0.1:1143/', false);
$client->addBeforeFilterHandler($logHandler2);
var_dump($client->hello("world"));
