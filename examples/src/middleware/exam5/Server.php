<?php
require_once "../../../vendor/autoload.php";
require_once '../../filter/CompressFilter.php';
require_once '../SizeHandler.php';
require_once '../StatHandler.php';
require_once '../StatHandler2.php';

use Hprose\Socket\Server;

$server = new Server('tcp://0.0.0.0:1143/');
$server->addFunction(function($value) { return $value; }, 'echo')
       ->addBeforeFilterHandler(array(new StatHandler2("BeforeFilter"), 'asynchandle'))
       ->addBeforeFilterHandler(array(new SizeHandler("compressedr"), 'asynchandle'))
       ->addFilter(new CompressFilter())
       ->addAfterFilterHandler(array(new StatHandler2("AfterFilter"), 'asynchandle'))
       ->addAfterFilterHandler(array(new SizeHandler("Non compressed"), 'asynchandle'))
       ->addInvokeHandler(array(new StatHandler("Invoke"), 'asynchandle'))
       ->start();
