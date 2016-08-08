<?php
require_once "../../../vendor/autoload.php";
require_once '../../filter/CompressFilter.php';
require_once '../SizeHandler.php';
require_once '../StatHandler.php';
require_once '../StatHandler2.php';
require_once '../CacheHandler2.php';

use Hprose\Client;
use Hprose\InvokeSettings;

$cacheSettings = new InvokeSettings(array("userdata" => array("cache" => true)));

$client = Client::create('tcp://127.0.0.1:1143/', false);
$client->addBeforeFilterHandler(array(new CacheHandler2(), 'handle'))
       ->addBeforeFilterHandler(array(new StatHandler2('BeforeFilter'), 'synchandle'))
       ->addBeforeFilterHandler(array(new SizeHandler('Non compressed'), 'synchandle'))
       ->addFilter(new CompressFilter())
       ->addAfterFilterHandler(array(new StatHandler2('AfterFilter'), 'synchandle'))
       ->addAfterFilterHandler(array(new SizeHandler('compressed'), 'synchandle'))
       ->addInvokeHandler(array(new StatHandler("Invoke"), 'synchandle'));

$value = range(0, 99999);
var_dump(count($client->echo($value, $cacheSettings)));
var_dump(count($client->echo($value, $cacheSettings)));

