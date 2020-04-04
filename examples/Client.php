<?php
require 'vendor/autoload.php';

use Hprose\RPC\Client;
use Hprose\RPC\Plugins\Log;

$client = new Client(['http://127.0.0.1:8024/']);
$log = new Log();
$client->use([$log, 'invokeHandler'], [$log, 'ioHandler']);
$proxy = $client->useService();
$result = $proxy->hello('world');
print($result);