<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$p = Future\promise(function($resolve, $reject) {
    $a = 1;
    $b = 2;
    if ($a != $b) {
        $resolve('OK');
    }
    else {
        $reject(new Exception("$a == $b"));
    }
});
$p->then(function($value) {
    var_dump($value);
});