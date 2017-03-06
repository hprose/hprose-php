<?php
require_once "../../vendor/autoload.php";

use Hprose\InvokeSettings;
use Hprose\Http\Client;
use Hprose\Socket\Server;
use Hprose\ResultMode;

$client = new Client("http://www.hprose.com/example/", false);
$settings = new InvokeSettings(array("mode" => ResultMode::RawWithEndTag));

$proxy = function($name, $args) use ($client, $settings) {
    return $client->invoke($name, $args, $settings);
};

$server = new Server("tcp://0.0.0.0:1314");
$server->debug = true;
$server->addMissingFunction($proxy, array("mode" => ResultMode::RawWithEndTag));
$server->start();
