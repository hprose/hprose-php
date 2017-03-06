<?php
require_once "../../../vendor/autoload.php";
require_once '../logHandler.php';
require_once '../CacheHandler.php';

use Hprose\Client;
use Hprose\InvokeSettings;

$cacheSettings = new InvokeSettings(array("userdata" => array("cache" => true)));
$client = Client::create('tcp://127.0.0.1:1143/', false);
$client->addInvokeHandler(array(new CacheHandler(), 'handle'));
$client->addInvokeHandler($logHandler);
var_dump($client->hello("cache world", $cacheSettings));
var_dump($client->hello("cache world", $cacheSettings));
var_dump($client->hello("no cache world"));
var_dump($client->hello("no cache world"));
