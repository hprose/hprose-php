<?php
require 'vendor/autoload.php';

use Hprose\RPC\Http\HttpServer;
use Hprose\RPC\Service;

function hello(string $name): string {
    return "Hello " . $name . "!";
}

$service = new Service();
$service->addCallable("hello", "hello");
$server = new HttpServer();
$service->bind($server);
$server->listen();