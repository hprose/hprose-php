<?php
require_once "../../vendor/autoload.php";

use Hprose\Socket\Server;

function hello($name, $context) {
    $context->clients->push("news", "this is a pushed message: $name");
    $context->clients->broadcast("news", array('x' => 1, 'y' => 2));
    return "Hello $name!";
}

$server = new Server("tcp://0.0.0.0:1980");
$server->publish('news');
$server->addFunction('hello', array('passContext' => true));
$server->start();
