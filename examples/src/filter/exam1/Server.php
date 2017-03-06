<?php
require_once "../../../vendor/autoload.php";
require_once '../LogFilter.php';

use Hprose\Socket\Server;

function hello($name) {
    return "Hello $name!";
}

$server = new Server('tcp://0.0.0.0:1143/');
$server->addFunction('hello');
$server->addFilter(new LogFilter());
$server->start();

