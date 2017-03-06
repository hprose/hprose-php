<?php
require_once "../vendor/autoload.php";

use \Hprose\Future;

class Test {
    function testco($x) {
        yield $x;
    }
}

$test = Future\wrap(new Test());

$test->testco(123)->then('var_dump');
$test->testco(Future\value('hello'))->then('var_dump');
