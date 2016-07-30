<?php
require_once "../../vendor/autoload.php";

use \Hprose\Future;
use \Hprose\Socket\Client;

Future\co(function() {
    $client = new Client("tcp://127.0.0.1:1980");
    $id = (yield $client->getId());
    $client->subscribe('news', $id, function($news) {
        var_dump($news);
    });
    var_dump((yield $client->hello('hprose')));
});
