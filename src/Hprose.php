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
 * LastModified: Jun 4, 2015                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

spl_autoload_register(function($className) {
    if (substr($className, 0, 6) === "Hprose") {
        if ($className{6} === '\\') {
            include str_replace("\\", "/", $className) . ".php";
        }
        else {
            switch ($className) {
                case 'HproseCompleter':
                    class_alias('Hprose\\Completer', 'HproseCompleter');
                    break;
                case 'HproseFuture':
                    class_alias('Hprose\\Future', 'HproseFuture');
                    break;
                case 'HproseTags':
                    class_alias('Hprose\\Tags', 'HproseTags');
                    break;
                case 'HproseBytesIO':
                    class_alias('Hprose\\BytesIO', 'HproseBytesIO');
                    break;
                case 'HproseClassManager':
                    class_alias('Hprose\\ClassManager', 'HproseClassManager');
                    break;
                case 'HproseRawReader':
                    class_alias('Hprose\\RawReader', 'HproseRawReader');
                    break;
                case 'HproseReader':
                    class_alias('Hprose\\Reader', 'HproseReader');
                    break;
                case 'HproseWriter':
                    class_alias('Hprose\\Writer', 'HproseWriter');
                    break;
                case 'HproseFormatter':
                    class_alias('Hprose\\Formatter', 'HproseFormatter');
                    break;
                case 'HproseResultMode':
                    class_alias('Hprose\\ResultMode', 'HproseResultMode');
                    break;
                case 'HproseFilter':
                    class_alias('Hprose\\Filter', 'HproseFilter');
                    break;
                case 'HproseClient':
                    class_alias('Hprose\\Client', 'HproseClient');
                    break;
                case 'HproseService':
                    class_alias('Hprose\\Service', 'HproseService');
                    break;
                case 'HproseBaseService':
                    class_alias('Hprose\\Base\\Service', 'HproseBaseService');
                    break;
                case 'HproseHttpClient':
                    class_alias('Hprose\\Http\\Client', 'HproseHttpClient');
                    break;
                case 'HproseHttpService':
                    class_alias('Hprose\\Http\\Service', 'HproseHttpService');
                    break;
                case 'HproseHttpServer':
                    class_alias('Hprose\\Http\\Server', 'HproseHttpServer');
                    break;
                case 'HproseSwooleClient':
                    class_alias('Hprose\\Swoole\\Client', 'HproseSwooleClient');
                    break;
                case 'HproseSwooleServer':
                    class_alias('Hprose\\Swoole\\Server', 'HproseSwooleServer');
                    break;
                case 'HproseSwooleHttpService':
                    class_alias('Hprose\\Swoole\\Http\\Service', 'HproseSwooleHttpService');
                    break;
                case 'HproseSwooleHttpServer':
                    class_alias('Hprose\\Swoole\\Http\\Server', 'HproseSwooleHttpServer');
                    break;
                case 'HproseSwooleSocketClient':
                    class_alias('Hprose\\Swoole\\Socket\\Client', 'HproseSwooleSocketClient');
                    break;
                case 'HproseSwooleSocketService':
                    class_alias('Hprose\\Swoole\\Socket\\Service', 'HproseSwooleSocketService');
                    break;
                case 'HproseSwooleSocketServer':
                    class_alias('Hprose\\Swoole\\Socket\\Server', 'HproseSwooleSocketServer');
                    break;
                case 'HproseSwooleWebSocketService':
                    class_alias('Hprose\\Swoole\\WebSocket\\Service', 'HproseSwooleWebSocketService');
                    break;
                case 'HproseSwooleWebSocketServer':
                    class_alias('Hprose\\Swoole\\WebSocket\\Server', 'HproseSwooleWebSocketServer');
                    break;
                case 'HproseSymfonyService':
                    class_alias('Hprose\\Symfony\\Service', 'HproseSymfonyService');
                    break;
                case 'HproseSymfonyServer':
                    class_alias('Hprose\\Symfony\\Server', 'HproseSymfonyServer');
                    break;
                case 'HproseYiiService':
                    class_alias('Hprose\\Yii\\Service', 'HproseYiiService');
                    break;
                case 'HproseYiiServer':
                    class_alias('Hprose\\Yii\\Server', 'HproseYiiServer');
                    break;
                case 'HproseJSONRPCClientFilter':
                    class_alias('Hprose\\Filter\\JSONRPC\\ClientFilter', 'HproseJSONRPCClientFilter');
                    break;
                case 'HproseJSONRPCServiceFilter':
                    class_alias('Hprose\\Filter\\JSONRPC\\ServiceFilter', 'HproseJSONRPCServiceFilter');
                    break;
                case 'HproseXMLRPCClientFilter':
                    class_alias('Hprose\\Filter\\XMLRPC\\ClientFilter', 'HproseXMLRPCClientFilter');
                    break;
                case 'HproseXMLRPCServiceFilter':
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

if (!function_exists('hprose_serialize')) {
    function hprose_serialize($var, $simple = false) {
        return \Hprose\Formatter::serialize($var, $simple);
    }
}
if (!function_exists('hprose_unserialize')) {
    function hprose_unserialize($data, $simple = false) {
        return \Hprose\Formatter::unserialize($data, $simple);
    }
}
