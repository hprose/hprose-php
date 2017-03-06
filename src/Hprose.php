<?php
/**********************************************************\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: http://www.hprose.com/                 |
|                   http://www.hprose.org/                 |
|                                                          |
\**********************************************************/

/**********************************************************\
 *                                                        *
 * Hprose.php                                             *
 *                                                        *
 * hprose for php 5.3+                                    *
 *                                                        *
 * LastModified: Jul 30, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

require_once 'Throwable.php';
require_once 'TypeError.php';
require_once 'Hprose/Future/functions.php';
require_once 'Hprose/Promise/functions.php';
require_once 'Hprose/functions.php';
require_once 'functions.php';

spl_autoload_register(function($className) {
    if ((strlen($className) > 6) && (strtolower(substr($className, 0, 6)) === "hprose")) {
        if ($className{6} === '\\') {
            include __DIR__ . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $className) . ".php";
        }
        else {
            // Deprecated
            // Compatible with older versions only
            // You'd better not use these classes.
            switch (strtolower($className)) {
                case 'hproseasync':
                    class_alias('Hprose\\Async', 'HproseAsync');
                    break;
                case 'hprosecompleter':
                    class_alias('Hprose\\Completer', 'HproseCompleter');
                    break;
                case 'hprosefuture':
                    class_alias('Hprose\\Future', 'HproseFuture');
                    break;
                case 'hprosetags':
                    class_alias('Hprose\\Tags', 'HproseTags');
                    break;
                case 'hprosebytesio':
                    class_alias('Hprose\\BytesIO', 'HproseBytesIO');
                    break;
                case 'hproseclassmanager':
                    class_alias('Hprose\\ClassManager', 'HproseClassManager');
                    break;
                case 'hproserawreader':
                    class_alias('Hprose\\RawReader', 'HproseRawReader');
                    break;
                case 'hprosereader':
                    class_alias('Hprose\\Reader', 'HproseReader');
                    break;
                case 'hprosewriter':
                    class_alias('Hprose\\Writer', 'HproseWriter');
                    break;
                case 'hproseformatter':
                    class_alias('Hprose\\Formatter', 'HproseFormatter');
                    break;
                case 'hproseresultmode':
                    class_alias('Hprose\\ResultMode', 'HproseResultMode');
                    break;
                case 'hprosefilter':
                    class_alias('Hprose\\Filter', 'HproseFilter');
                    break;
                case 'hproseclient':
                    class_alias('Hprose\\Client', 'HproseClient');
                    break;
                case 'hproseservice':
                    class_alias('Hprose\\Service', 'HproseService');
                    break;
                case 'hprosehttpclient':
                    class_alias('Hprose\\Http\\Client', 'HproseHttpClient');
                    break;
                case 'hprosehttpservice':
                    class_alias('Hprose\\Http\\Service', 'HproseHttpService');
                    break;
                case 'hprosehttpserver':
                    class_alias('Hprose\\Http\\Server', 'HproseHttpServer');
                    break;
                case 'hprosesocketclient':
                    class_alias('Hprose\\Socket\\Client', 'HproseSocketClient');
                    break;
                case 'hprosesocketservice':
                    class_alias('Hprose\\Socket\\Service', 'HproseSocketService');
                    break;
                case 'hprosesocketserver':
                    class_alias('Hprose\\Socket\\Server', 'HproseSocketServer');
                    break;
                case 'hprosejsonrpcclientfilter':
                    class_alias('Hprose\\Filter\\JSONRPC\\ClientFilter', 'HproseJSONRPCClientFilter');
                    break;
                case 'hprosejsonrpcservicefilter':
                    class_alias('Hprose\\Filter\\JSONRPC\\ServiceFilter', 'HproseJSONRPCServiceFilter');
                    break;
                case 'hprosexmlrpcclientfilter':
                    class_alias('Hprose\\Filter\\XMLRPC\\ClientFilter', 'HproseXMLRPCClientFilter');
                    break;
                case 'hprosexmlrpcservicefilter':
                    class_alias('Hprose\\Filter\\XMLRPC\\ServiceFilter', 'HproseXMLRPCServiceFilter');
                    break;
                default:
                    return false;
            }
        }
        return true;
    }
    return false;
});
