<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

function add($a, $b) {
    return $a + $b;
}

$p1 = Future\resolve(3);

Future\run('add', 2, $p1)->then('var_dump');