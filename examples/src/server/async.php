<?php
require_once "../../vendor/autoload.php";

use Hprose\Http\Server;

function hello($name, $callback) {
    $callback("Hello $name!");
}

$server = new Server();
$server->addFunction('hello', array("async" => true));
$server->start();
