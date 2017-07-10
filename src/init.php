<?php

require_once __DIR__ . '/Throwable.php';
require_once __DIR__ . '/TypeError.php';
require_once __DIR__ . '/Hprose/Future/functions.php';
require_once __DIR__ . '/Hprose/Promise/functions.php';
require_once __DIR__ . '/Hprose/functions.php';
require_once __DIR__ . '/functions.php';

// Deprecated
// Compatible with older versions only
// You'd better not use these classes.
spl_autoload_register(function ($className) {
    $oldVersionAliases = array(
//        'hproseasync' => array('Hprose\\Async', 'HproseAsync'), // Hprose\Async not found
        'hprosecompleter' => array('Hprose\\Completer', 'HproseCompleter'),
        'hprosefuture' => array('Hprose\\Future', 'HproseFuture'),
        'hprosetags' => array('Hprose\\Tags', 'HproseTags'),
        'hprosebytesio' => array('Hprose\\BytesIO', 'HproseBytesIO'),
        'hproseclassmanager' => array('Hprose\\ClassManager', 'HproseClassManager'),
        'hproserawreader' => array('Hprose\\RawReader', 'HproseRawReader'),
        'hprosereader' => array('Hprose\\Reader', 'HproseReader'),
        'hprosewriter' => array('Hprose\\Writer', 'HproseWriter'),
        'hproseformatter' => array('Hprose\\Formatter', 'HproseFormatter'),
        'hproseresultmode' => array('Hprose\\ResultMode', 'HproseResultMode'),
        'hprosefilter' => array('Hprose\\Filter', 'HproseFilter'),
        'hproseclient' => array('Hprose\\Client', 'HproseClient'),
        'hproseservice' => array('Hprose\\Service', 'HproseService'),
        'hprosehttpclient' => array('Hprose\\Http\\Client', 'HproseHttpClient'),
        'hprosehttpservice' => array('Hprose\\Http\\Service', 'HproseHttpService'),
        'hprosehttpserver' => array('Hprose\\Http\\Server', 'HproseHttpServer'),
        'hprosesocketclient' => array('Hprose\\Socket\\Client', 'HproseSocketClient'),
        'hprosesocketservice' => array('Hprose\\Socket\\Service', 'HproseSocketService'),
        'hprosesocketserver' => array('Hprose\\Socket\\Server', 'HproseSocketServer'),
        'hprosejsonrpcclientfilter' => array('Hprose\\Filter\\JSONRPC\\ClientFilter', 'HproseJSONRPCClientFilter'),
        'hprosejsonrpcservicefilter' => array('Hprose\\Filter\\JSONRPC\\ServiceFilter', 'HproseJSONRPCServiceFilter'),
        'hprosexmlrpcclientfilter' => array('Hprose\\Filter\\XMLRPC\\ClientFilter', 'HproseXMLRPCClientFilter'),
        'hprosexmlrpcservicefilter' => array('Hprose\\Filter\\XMLRPC\\ServiceFilter', 'HproseXMLRPCServiceFilter'),
    );

    if (isset($oldVersionAliases[$key = strtolower($className)])) {
        list($original, $alias) = $oldVersionAliases[$key];
        class_alias($original, $alias);
        return true;
    }
    return false;
});
