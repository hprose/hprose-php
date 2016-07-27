<?php
require_once "../../vendor/autoload.php";

use Hprose\Client;

$client = Client::create('http://hprose.com/example/');

$client->sum(1, 2)
       ->then(function($result) use ($client) {
            return $client->sum($result, 3);
       })
       ->then(function($result) use ($client) {
            return $client->sum($result, 4);
       })
       ->then(function($result) {
            var_dump($result);
       });