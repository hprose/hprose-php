<?php
require_once "../../vendor/autoload.php";

use Hprose\Client;
use Hprose\Future;

Future\co(function() {
    $client = Client::create('http://hprose.com/example/');
    $sum = $client->sum;
    try {
        (yield $client->ooxx());
    }
    catch (Exception $e) {
        try {
            var_dump((yield $sum(1, 2)));
        }
        catch (Exception $e) {
            var_dump($e);
        }
    }
});
