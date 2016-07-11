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
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

require_once 'Throwable.php';
require_once 'TypeError.php';
require_once 'Hprose/Future/functions.php';
require_once 'Hprose/Promise/functions.php';
require_once 'Hprose/functions.php';

spl_autoload_register(function($className) {
    if (strtolower(substr($className, 0, 6)) === "hprose") {
        if ($className{6} === '\\') {
            include __DIR__ . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $className) . ".php";
        }
        else {
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
                case 'hproseinvokesettings':
                    class_alias('Hprose\\InvokeSettings', 'HproseInvokeSettings');
                    break;
                case 'hprosehandlermanager':
                    class_alias('Hprose\\HandlerManager', 'HproseHandlerManager');
                    break;
                case 'hproseclient':
                    class_alias('Hprose\\Client', 'HproseClient');
                    break;
                case 'hproseservice':
                    class_alias('Hprose\\Service', 'HproseService');
                    break;
                case 'hprosebaseservice':
                    class_alias('Hprose\\Base\\Service', 'HproseBaseService');
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
                case 'hproseswooleclient':
                    class_alias('Hprose\\Swoole\\Client', 'HproseSwooleClient');
                    break;
                case 'hproseswooleserver':
                    class_alias('Hprose\\Swoole\\Server', 'HproseSwooleServer');
                    break;
                case 'hproseswoolehttpservice':
                    class_alias('Hprose\\Swoole\\Http\\Service', 'HproseSwooleHttpService');
                    break;
                case 'hproseswoolehttpserver':
                    class_alias('Hprose\\Swoole\\Http\\Server', 'HproseSwooleHttpServer');
                    break;
                case 'hproseswoolesocketclient':
                    class_alias('Hprose\\Swoole\\Socket\\Client', 'HproseSwooleSocketClient');
                    break;
                case 'hproseswoolesocketservice':
                    class_alias('Hprose\\Swoole\\Socket\\Service', 'HproseSwooleSocketService');
                    break;
                case 'hproseswoolesocketserver':
                    class_alias('Hprose\\Swoole\\Socket\\Server', 'HproseSwooleSocketServer');
                    break;
                case 'hproseswoolewebsocketservice':
                    class_alias('Hprose\\Swoole\\WebSocket\\Service', 'HproseSwooleWebSocketService');
                    break;
                case 'hproseswoolewebsocketserver':
                    class_alias('Hprose\\Swoole\\WebSocket\\Server', 'HproseSwooleWebSocketServer');
                    break;
                case 'hprosesymfonyservice':
                    class_alias('Hprose\\Symfony\\Service', 'HproseSymfonyService');
                    break;
                case 'hprosesymfonyserver':
                    class_alias('Hprose\\Symfony\\Server', 'HproseSymfonyServer');
                    break;
                case 'hproseyiiservice':
                    class_alias('Hprose\\Yii\\Service', 'HproseYiiService');
                    break;
                case 'hproseyiiserver':
                    class_alias('Hprose\\Yii\\Server', 'HproseYiiServer');
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

require_once 'functions.php';
