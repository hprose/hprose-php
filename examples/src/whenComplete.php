<?php

require_once "../vendor/autoload.php";

use Hprose\Future;

$p1 = Future\resolve('resolve hprose');

$p1->whenComplete(function() {
    var_dump('p1 complete');
})->then(function($value) {
    var_dump($value);
});

$p2 = Future\reject(new Exception('reject thrift'));

$p2->whenComplete(function() {
    var_dump('p2 complete');
})->catchError(function($reason) {
    var_dump($reason->getMessage());
});

$p3 = Future\resolve('resolve protobuf');

$p3->whenComplete(function() {
    var_dump('p3 complete');
    throw new Exception('reject protobuf');
})->catchError(function($reason) {
    var_dump($reason->getMessage());
});
