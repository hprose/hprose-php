<?php
require_once "../../../vendor/autoload.php";
require_once '../CompressFilter.php';
require_once '../SizeFilter.php';

use Hprose\Socket\Server;

$server = new Server('tcp://0.0.0.0:1143/');
$server->addFilter(new SizeFilter('Non compressed'));
$server->addFilter(new CompressFilter());
$server->addFilter(new SizeFilter('Compressed'));
$server->addFunction(function($value) {
    return $value;
}, 'echo');
$server->start();
