<?php
require_once "../../vendor/autoload.php";

use Hprose\Client;

$client = Client::create('http://hprose.com/example/', false);

$weeks = array(
    'Monday' => 'Mon',
    'Tuesday' => 'Tue',
    'Wednesday' => 'Wed',
    'Thursday' => 'Thu',
    'Friday' => 'Fri',
    'Saturday' => 'Sat',
    'Sunday' => 'Sun'
);

$args = array($weeks);

$client->swapKeyAndValue["byref"] = true;
$client->swapKeyAndValue($weeks, function($result, $args) {
    var_dump($args[0]);
});