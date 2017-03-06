<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

function dumpArray($value, $key) {
  var_dump("a[$key] = $value");
}

$a1 = array(2, Future\value(5), 9);
$a2 = array('name' => Future\value('Tom'), 'age' => Future\value(18));
Future\each($a1, 'dumpArray');
Future\each($a2, 'dumpArray');
