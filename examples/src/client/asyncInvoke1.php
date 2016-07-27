<?php
require_once "../../vendor/autoload.php";

use Hprose\Client;

$client = Client::create('http://hprose.com/example/');

$client->sum($client->sum($client->sum(1, 2), 3), 4)
       ->then(function($result) {
            var_dump($result);
       });
       