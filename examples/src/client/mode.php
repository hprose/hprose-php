<?php
require_once "../../vendor/autoload.php";

use Hprose\Client;
use Hprose\InvokeSettings;
use Hprose\ResultMode;

$client = Client::create('http://hprose.com/example/', false);

var_dump($client->hello("World", new InvokeSettings(array('mode' => ResultMode::Normal))));
var_dump($client->hello("World", new InvokeSettings(array('mode' => ResultMode::Serialized))));
var_dump($client->hello("World", new InvokeSettings(array('mode' => ResultMode::Raw))));
var_dump($client->hello("World", new InvokeSettings(array('mode' => ResultMode::RawWithEndTag))));
