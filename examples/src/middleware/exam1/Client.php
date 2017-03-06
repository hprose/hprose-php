<?php
require_once "../../../vendor/autoload.php";
require_once '../logHandler.php';

use Hprose\Client;

$client = Client::create('tcp://127.0.0.1:1143/', false);
$client->addInvokeHandler($logHandler);
var_dump($client->hello("world"));
