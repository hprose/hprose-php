<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

class Test {
    function add($a, $b) {
        return $a + $b;
    }
    function sub($a, $b) {
        return $a - $b;
    }
    function mul($a, $b) {
        return $a * $b;
    }
    function div($a, $b) {
        return $a / $b;
    }
}

$var_dump = Future\wrap('var_dump');

$test = Future\wrap(new Test());

$var_dump($test->add(1, Future\value(2)));
$var_dump($test->sub(Future\value(1), 2));
$var_dump($test->mul(Future\value(1), Future\value(2)));
$var_dump($test->div(1, 2));
