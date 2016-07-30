<?php
require_once "../../vendor/autoload.php";

use Hprose\Socket\Server;

$server = new Server("tcp://0.0.0.0:2016");
$server->publish('time');
$server->tick(1000, function() use ($server) {
    error_log(microtime(true));
    $server->push('time', microtime(true));
});
$server->start();
