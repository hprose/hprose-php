<?php
require_once "../../vendor/autoload.php";

use Hprose\Client;
use Hprose\InvokeSettings;

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
$client->invoke('swapKeyAndValue', $args, new InvokeSettings(array('byref' => true)));
var_dump($args[0]);

$client->swapKeyAndValue($weeks, function($result, $args) {
    var_dump($args[0]);
}, array('byref' => true));