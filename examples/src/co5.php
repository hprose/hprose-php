<?php
require_once "../vendor/autoload.php";

use \Hprose\Future;

class Test {
    function test() {
        yield 123;
    }
}

$test = Future\wrap(new Test());

$test->test()->then('var_dump');