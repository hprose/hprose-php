<?php
require_once "../vendor/autoload.php";

use Hprose\Future;

var_dump(Future\isFuture(123));
var_dump(Future\isFuture(Future\value(123)));
