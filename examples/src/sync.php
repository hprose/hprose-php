<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$p1 = new Future(function() {
    return array(Future\value(1), Future\value(2));
});
$p1->then(function($value) {
    var_dump($value);
});

$p2 = Future\sync(function() {
    return array(Future\value(1), Future\value(2));
});
$p2->then(function($value) {
    var_dump($value);
});

