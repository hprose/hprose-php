<?php
require_once "../vendor/autoload.php";

use \Hprose\Future;

class Test {
    function test($x) {
        yield $x;
    }
}

$test = Future\wrap(new Test());

$test->test(123)->then('var_dump');
$test->test(Future\value('hello'))->then('var_dump');