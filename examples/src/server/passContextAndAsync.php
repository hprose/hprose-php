<?php
require_once "../../vendor/autoload.php";

use Hprose\Socket\Server;

function hello($name, $context, $callback) {
    $callback("Hello $name! -- " . stream_socket_get_name($context->socket, true));
}

$server = new Server("tcp://0.0.0.0:1314");
$server->addFunction('hello', array("async" => true, "passContext" => true));
$server->start();
