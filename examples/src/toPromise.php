<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$a = array(1, Future\value(2), 3, Future\value(4), 5);

$o = new \stdClass();
$o->name = Future\value("Tom");
$o->age = Future\value(18);

Future\toFuture($a)->then(function($value) {
    var_dump($value);
});

Future\toFuture($o)->then(function($value) {
    var_dump($value);
});

Future\toPromise($a)->then(function($value) {
    var_dump($value);
});

Future\toPromise($o)->then(function($value) {
    var_dump($value);
});