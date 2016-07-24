<?php
require_once "../vendor/autoload.php";

use \Hprose\Future;
use \Hprose\Http\Client;

$test = new Client("http://hprose.com/example/");

Future\co(function() use ($test) {
    for ($i = 0; $i < 5; $i++) {
        var_dump((yield $test->hello("1-" . $i)));
    }
});

Future\co(function() use ($test) {
    for ($i = 0; $i < 5; $i++) {
        var_dump((yield $test->hello("2-" . $i)));
    }
});
