<?php
require_once "../../../vendor/autoload.php";
require_once '../coLogHandler.php';

use Hprose\Socket\Server;

function hello($name) {
    return "Hello $name!";
}

$server = new Server('tcp://0.0.0.0:1143/');
$server->addFunction('hello');
$server->debug = true;
$server->addInvokeHandler($coLogHandler);
$server->start();

