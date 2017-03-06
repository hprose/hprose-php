<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

$p = Future\reject(new OutOfRangeException());

$p->catchError(function($reason) { return 'this is a OverflowException'; },
               function($reason) { return $reason instanceof OverflowException; })
  ->catchError(function($reason) { return 'this is a OutOfRangeException'; },
               function($reason) { return $reason instanceof OutOfRangeException; })
  ->then(function($value) { var_dump($value);  });