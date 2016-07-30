<?php
require_once "../../vendor/autoload.php";

use \Hprose\Socket\Client;

$client = new Client("tcp://127.0.0.1:2016");
$count = 0;
$client->subscribe('time', function($date) use ($client, &$count) {
    if (++$count > 10) {
        $client->unsubscribe('time');
        swoole_event_exit();
    }
    else {
        var_dump($date);
    }
});
