<?php
require_once "../vendor/autoload.php";

use Hprose\Completer;

$completer = new Completer();
$promise = $completer->future();
$promise->then(function($value) {
    var_dump($value);
});
var_dump($completer->isCompleted());
$completer->complete('hprose');
var_dump($completer->isCompleted());
