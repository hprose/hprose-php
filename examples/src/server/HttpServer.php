<?php
require_once "../../vendor/autoload.php";

use Hprose\Http\Server;

function hello($name) {
    return "Hello $name!";
}

function sum($a, $b, $c) {
    return $a + $b + $c;
}

$server = new Server();
$server->addFunction('hello');
$server->addFunction('sum');
$server->start();
