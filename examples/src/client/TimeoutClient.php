<?php
require_once "../../vendor/autoload.php";

use \Hprose\Socket\Client;
use \Hprose\InvokeSettings;

$test = new Client("tcp://127.0.0.1:1315");
$test->fullDuplex = true;
$test->timeout = 600;

$test->sum(1, 2)->catchError(function($e) {
    //echo $e;
});
$test->sum(1, 2)->then(function($result) {
    echo "1 + 2 = " . $result;
})->catchError(function($e) use ($test) {
    //echo $e;
    $test->sum(2, 3, new InvokeSettings(array('timeout' => 20000)))
    ->then(function($result) {
        echo "2 + 3 = " . $result;
    })->catchError(function($e) {
        echo $e;
    });
});

$test->loop();