<?php
require_once "../vendor/autoload.php";

use \Hprose\Future;
use \Hprose\Http\Client;

$test = new Client("http://hprose.com/example/");

function hello($n, $test) {
    $result = array();
    for ($i = 0; $i < 5; $i++) {
        $result[] = $test->hello("$n-$i");
    }
    yield Future\all($result);
}

Future\co(function() use ($test) {
    $result = (yield Future\co(function($test) {
        $result = array();
        for ($i = 0; $i < 3; $i++) {
             $result[] = Future\co('hello', $i, $test);
        }
        yield Future\all($result);
    }, $test));

    var_dump($result);
});
