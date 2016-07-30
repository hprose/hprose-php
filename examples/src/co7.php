<?php
require_once "../vendor/autoload.php";

use Hprose\Client;
use Hprose\Future;

Future\co(function() {
    $client = Client::create('http://hprose.com/example/');
    (yield $client->oo());
    (yield $client->xx());
})->catchError(function($e) {
    echo $e->getMessage();
});
