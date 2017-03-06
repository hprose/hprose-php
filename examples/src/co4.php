<?php
require_once "../vendor/autoload.php";

use \Hprose\Future;
use \Hprose\Http\Client;

$test = new Client("http://hprose.com/example/");

$coroutine = Future\wrap(function($test) {
    var_dump(1);
    var_dump((yield $test->hello("hprose")));
    $a = $test->sum(1, 2, 3);
    $b = $test->sum(4, 5, 6);
    $c = $test->sum(7, 8, 9);
    var_dump((yield $test->sum($a, $b, $c)));
    var_dump((yield $test->hello("world")));
});

$coroutine($test);
$coroutine(Future\value($test));