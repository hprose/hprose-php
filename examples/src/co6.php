<?php
require_once "../vendor/autoload.php";

use Hprose\Client;
use Hprose\Future;

Future\co(function() {
    $client = Client::create('http://hprose.com/example/');
    try {
        (yield $client->ooxx());
    }
    catch (Exception $e) {
        echo $e->getMessage();
    }
});
