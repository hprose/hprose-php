<?php
require_once "../../../vendor/autoload.php";
require_once '../coLogHandler.php';

use Hprose\Client;
use Hprose\Future;

$client = Client::create('tcp://127.0.0.1:1143/');
$client->addInvokeHandler($coLogHandler);
$var_dump = Future\wrap('var_dump');
$var_dump($client->hello("world"));
