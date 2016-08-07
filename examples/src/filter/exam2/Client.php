<?php
require_once "../../../vendor/autoload.php";
require_once '../CompressFilter.php';
require_once '../SizeFilter.php';

use Hprose\Client;

$client = Client::create('tcp://127.0.0.1:1143/', false);
$client->addFilter(new SizeFilter('Non compressed'));
$client->addFilter(new CompressFilter());
$client->addFilter(new SizeFilter('Compressed'));

$value = range(0, 99999);
var_dump(count($client->echo($value)));
