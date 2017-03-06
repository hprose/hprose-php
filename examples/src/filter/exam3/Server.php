<?php
require_once "../../../vendor/autoload.php";
require_once '../StatFilter.php';

use Hprose\Socket\Server;

$server = new Server('tcp://0.0.0.0:1143/');
$server->addFilter(new StatFilter());
$server->addFunction(function($value) {
    return $value;
}, 'echo');
$server->start();
