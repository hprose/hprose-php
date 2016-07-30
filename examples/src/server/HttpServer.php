<?php
require_once "../../vendor/autoload.php";

use Hprose\Http\Server;

function hello($name) {
    return "Hello $name!";
}

$server = new Server();
$server->addFunction('hello');
$server->start();
