<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

Future\all(array(1, Future\value(2), 3))->then(function($value) {
    var_dump($value);
});

Future\join(1, Future\value(2), 3)->then(function($value) {
    var_dump($value);
});