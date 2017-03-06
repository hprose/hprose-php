<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

function dumpArray($value, $key) {
  var_dump("a[$key] = $value");
}

$a1 = Future\value(array(2, Future\value(5), 9));
$a2 = Future\value(array('name' => Future\value('Tom'), 'age' => Future\value(18)));
$a1->each('dumpArray');
$a2->each('dumpArray');
