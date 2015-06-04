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
    if (strtolower(substr($className, 0, 6)) === "hprose") {
        if ($className{6} === '\\') {
            include str_replace("\\", "/", $className) . ".php";
        }
        else {
            switch (strtolower($className)) {
                case 'hprosecompleter':
                    class_alias('Hprose\\Completer', 'HproseCompleter');
                    break;
                case 'hprose_completer':
                    class_alias('Hprose\\Completer', 'Hprose_Completer');
                    break;
                case 'hprosefuture':
                    class_alias('Hprose\\Future', 'HproseFuture');
                    break;
                case 'hprose_future':
                    class_alias('Hprose\\Future', 'Hprose_Future');
                    break;
                case 'hprosetags':
                    class_alias('Hprose\\Tags', 'HproseTags');
                    break;
                case 'hprose_tags':
                    class_alias('Hprose\\Tags', 'Hprose_Tags');
                    break;
                case 'hprosebytesio':
                    class_alias('Hprose\\BytesIO', 'HproseBytesIO');
                    break;
                case 'hprose_bytesio':
                    class_alias('Hprose\\BytesIO', 'Hprose_BytesIO');
                    break;
                case 'hprose_bytes_io':
                    class_alias('Hprose\\BytesIO', 'Hprose_Bytes_IO');
                    break;
                case 'hproseclassmanager':
                    class_alias('Hprose\\ClassManager', 'HproseClassManager');
                    break;
                case 'hprose_classmanager':
                    class_alias('Hprose\\ClassManager', 'Hprose_ClassManager');
                    break;
                case 'hprose_class_manager':
                    class_alias('Hprose\\ClassManager', 'Hprose_Class_Manager');
                    break;
                case 'hproserawreader':
                    class_alias('Hprose\\RawReader', 'HproseRawReader');
                    break;
                case 'hprose_rawreader':
                    class_alias('Hprose\\RawReader', 'Hprose_RawReader');
                    break;
                case 'hprose_raw_reader':
                    class_alias('Hprose\\RawReader', 'Hprose_Raw_Reader');
                    break;
                case 'hprosereader':
                    class_alias('Hprose\\Reader', 'HproseReader');
                    break;
                case 'hprose_reader':
                    class_alias('Hprose\\Reader', 'Hprose_Reader');
                    break;
                case 'hprosewriter':
                    class_alias('Hprose\\Writer', 'HproseWriter');
                    break;
                case 'hprose_writer':
                    class_alias('Hprose\\Writer', 'Hprose_Writer');
                    break;
                case 'hproseformatter':
                    class_alias('Hprose\\Formatter', 'HproseFormatter');
                    break;
                case 'hprose_formatter':
                    class_alias('Hprose\\Formatter', 'Hprose_Formatter');
                    break;
                case 'hproseresultmode':
                    class_alias('Hprose\\ResultMode', 'HproseResultMode');
                    break;
                case 'hprose_resultmode':
                    class_alias('Hprose\\ResultMode', 'Hprose_ResultMode');
                    break;
                case 'hprose_result_mode':
                    class_alias('Hprose\\ResultMode', 'Hprose_Result_Mode');
                    break;
                case 'hprosefilter':
                    class_alias('Hprose\\Filter', 'HproseFilter');
                    break;
                case 'hprose_filter':
                    class_alias('Hprose\\Filter', 'Hprose_Filter');
                    break;
                case 'hproseclient':
                    class_alias('Hprose\\Client', 'HproseClient');
                    break;
                case 'hprose_client':
                    class_alias('Hprose\\Client', 'Hprose_Client');
                    break;
                case 'hproseservice':
                    class_alias('Hprose\\Service', 'HproseService');
                    break;
                case 'hprose_service':
                    class_alias('Hprose\\Service', 'Hprose_Service');
                    break;
                case 'hprosebaseservice':
                    class_alias('Hprose\\Base\\Service', 'HproseBaseService');
                    break;
                case 'hprose_baseservice':
                    class_alias('Hprose\\Base\\Service', 'Hprose_BaseService');
                    break;
                case 'hprose_base_service':
                    class_alias('Hprose\\Base\\Service', 'Hprose_Base_Service');
                    break;
                case 'hprosehttpclient':
                    class_alias('Hprose\\Http\\Client', 'HproseHttpClient');
                    break;
                case 'hprose_httpclient':
                    class_alias('Hprose\\Http\\Client', 'Hprose_HttpClient');
                    break;
                case 'hprose_http_client':
                    class_alias('Hprose\\Http\\Client', 'Hprose_Http_Client');
                    break;
                case 'hprosehttpservice':
                    class_alias('Hprose\\Http\\Service', 'HproseHttpService');
                    break;
                case 'hprose_httpservice':
                    class_alias('Hprose\\Http\\Service', 'Hprose_HttpService');
                    break;
                case 'hprose_http_service':
                    class_alias('Hprose\\Http\\Service', 'Hprose_Http_Service');
                    break;
                case 'hprosehttpserver':
                    class_alias('Hprose\\Http\\Server', 'HproseHttpServer');
                    break;
                case 'hprose_httpserver':
                    class_alias('Hprose\\Http\\Server', 'Hprose_HttpServer');
                    break;
                case 'hprose_http_server':
                    class_alias('Hprose\\Http\\Server', 'Hprose_Http_Server');
                    break;
                case 'hproseswooleclient':
                    class_alias('Hprose\\Swoole\\Client', 'HproseSwooleClient');
                    break;
                case 'hprose_swooleclient':
                    class_alias('Hprose\\Swoole\\Client', 'Hprose_SwooleClient');
                    break;
                case 'hprose_swoole_client':
                    class_alias('Hprose\\Swoole\\Client', 'Hprose_Swoole_Client');
                    break;
                case 'hproseswooleserver':
                    class_alias('Hprose\\Swoole\\Server', 'HproseSwooleServer');
                    break;
                case 'hprose_swooleserver':
                    class_alias('Hprose\\Swoole\\Server', 'Hprose_SwooleServer');
                    break;
                case 'hprose_swoole_server':
                    class_alias('Hprose\\Swoole\\Server', 'Hprose_Swoole_Server');
                    break;
                case 'hproseswoolehttpservice':
                    class_alias('Hprose\\Swoole\\Http\\Service', 'HproseSwooleHttpService');
                    break;
                case 'hprose_swoolehttpservice':
                    class_alias('Hprose\\Swoole\\Http\\Service', 'Hprose_SwooleHttpService');
                    break;
                case 'hprose_swoole_httpservice':
                    class_alias('Hprose\\Swoole\\Http\\Service', 'Hprose_Swoole_HttpService');
                    break;
                case 'hprose_swoole_http_service':
                    class_alias('Hprose\\Swoole\\Http\\Service', 'Hprose_Swoole_Http_Service');
                    break;
                case 'hproseswoolehttpserver':
                    class_alias('Hprose\\Swoole\\Http\\Server', 'HproseSwooleHttpServer');
                    break;
                case 'hprose_swoolehttpserver':
                    class_alias('Hprose\\Swoole\\Http\\Server', 'Hprose_SwooleHttpServer');
                    break;
                case 'hprose_swoole_httpserver':
                    class_alias('Hprose\\Swoole\\Http\\Server', 'Hprose_Swoole_HttpServer');
                    break;
                case 'hprose_swoole_http_server':
                    class_alias('Hprose\\Swoole\\Http\\Server', 'Hprose_Swoole_Http_Server');
                    break;
                case 'hproseswoolesocketclient':
                    class_alias('Hprose\\Swoole\\Socket\\Client', 'HproseSwooleSocketClient');
                    break;
                case 'hprose_swoolesocketclient':
                    class_alias('Hprose\\Swoole\\Socket\\Client', 'Hprose_SwooleSocketClient');
                    break;
                case 'hprose_swoole_socketclient':
                    class_alias('Hprose\\Swoole\\Socket\\Client', 'Hprose_Swoole_SocketClient');
                    break;
                case 'hprose_swoole_socket_client':
                    class_alias('Hprose\\Swoole\\Socket\\Client', 'Hprose_Swoole_Socket_Client');
                    break;
                case 'hproseswoolesocketservice':
                    class_alias('Hprose\\Swoole\\Socket\\Service', 'HproseSwooleSocketService');
                    break;
                case 'hprose_swoolesocketservice':
                    class_alias('Hprose\\Swoole\\Socket\\Service', 'Hprose_SwooleSocketService');
                    break;
                case 'hprose_swoole_socketservice':
                    class_alias('Hprose\\Swoole\\Socket\\Service', 'Hprose_Swoole_SocketService');
                    break;
                case 'hprose_swoole_socket_service':
                    class_alias('Hprose\\Swoole\\Socket\\Service', 'Hprose_Swoole_Socket_Service');
                    break;
                case 'hproseswoolesocketserver':
                    class_alias('Hprose\\Swoole\\Socket\\Server', 'HproseSwooleSocketServer');
                    break;
                case 'hprose_swoolesocketserver':
                    class_alias('Hprose\\Swoole\\Socket\\Server', 'Hprose_SwooleSocketServer');
                    break;
                case 'hprose_swoole_socketserver':
                    class_alias('Hprose\\Swoole\\Socket\\Server', 'Hprose_Swoole_SocketServer');
                    break;
                case 'hprose_swoole_socket_server':
                    class_alias('Hprose\\Swoole\\Socket\\Server', 'Hprose_Swoole_Socket_Server');
                    break;
                case 'hproseswoolewebsocketservice':
                    class_alias('Hprose\\Swoole\\WebSocket\\Service', 'HproseSwooleWebSocketService');
                    break;
                case 'hprose_swoolewebsocketservice':
                    class_alias('Hprose\\Swoole\\WebSocket\\Service', 'Hprose_SwooleWebSocketService');
                    break;
                case 'hprose_swoole_websocketservice':
                    class_alias('Hprose\\Swoole\\WebSocket\\Service', 'Hprose_Swoole_WebSocketService');
                    break;
                case 'hprose_swoole_websocket_service':
                    class_alias('Hprose\\Swoole\\WebSocket\\Service', 'Hprose_Swoole_WebSocket_Service');
                    break;
                case 'hproseswoolewebsocketserver':
                    class_alias('Hprose\\Swoole\\WebSocket\\Server', 'HproseSwooleWebSocketServer');
                    break;
                case 'hprose_swoolewebsocketserver':
                    class_alias('Hprose\\Swoole\\WebSocket\\Server', 'Hprose_SwooleWebSocketServer');
                    break;
                case 'hprose_swoole_websocketserver':
                    class_alias('Hprose\\Swoole\\WebSocket\\Server', 'Hprose_Swoole_WebSocketServer');
                    break;
                case 'hprose_swoole_websocket_server':
                    class_alias('Hprose\\Swoole\\WebSocket\\Server', 'Hprose_Swoole_WebSocket_Server');
                    break;
                case 'hprosesymfonyservice':
                    class_alias('Hprose\\Symfony\\Service', 'HproseSymfonyService');
                    break;
                case 'hprose_symfonyservice':
                    class_alias('Hprose\\Symfony\\Service', 'Hprose_SymfonyService');
                    break;
                case 'hprose_symfony_service':
                    class_alias('Hprose\\Symfony\\Service', 'Hprose_Symfony_Service');
                    break;
                case 'hprosesymfonyserver':
                    class_alias('Hprose\\Symfony\\Server', 'HproseSymfonyServer');
                    break;
                case 'hprose_symfonyserver':
                    class_alias('Hprose\\Symfony\\Server', 'Hprose_SymfonyServer');
                    break;
                case 'hprose_symfony_server':
                    class_alias('Hprose\\Symfony\\Server', 'Hprose_Symfony_Server');
                    break;
                case 'hproseyiiservice':
                    class_alias('Hprose\\Yii\\Service', 'HproseYiiService');
                    break;
                case 'hprose_yiiservice':
                    class_alias('Hprose\\Yii\\Service', 'Hprose_YiiService');
                    break;
                case 'hprose_yii_service':
                    class_alias('Hprose\\Yii\\Service', 'Hprose_Yii_Service');
                    break;
                case 'hproseyiiserver':
                    class_alias('Hprose\\Yii\\Server', 'HproseYiiServer');
                    break;
                case 'hprose_yiiserver':
                    class_alias('Hprose\\Yii\\Server', 'Hprose_YiiServer');
                    break;
                case 'hprose_yii_server':
                    class_alias('Hprose\\Yii\\Server', 'Hprose_Yii_Server');
                    break;
                case 'hprosejsonrpcclientfilter':
                    class_alias('Hprose\\Filter\\JSONRPC\\ClientFilter', 'HproseJSONRPCClientFilter');
                    break;
                case 'hprose_jsonrpc_clientfilter':
                    class_alias('Hprose\\Filter\\JSONRPC\\ClientFilter', 'Hprose_JSONRPC_ClientFilter');
                    break;
                case 'hprose_jsonrpc_client_filter':
                    class_alias('Hprose\\Filter\\JSONRPC\\ClientFilter', 'Hprose_JSONRPC_Client_Filter');
                    break;
                case 'hprosejsonrpcservicefilter':
                    class_alias('Hprose\\Filter\\JSONRPC\\ServiceFilter', 'HproseJSONRPCServiceFilter');
                    break;
                case 'hprose_jsonrpc_servicefilter':
                    class_alias('Hprose\\Filter\\JSONRPC\\ServiceFilter', 'Hprose_JSONRPC_ServiceFilter');
                    break;
                case 'hprose_jsonrpc_service_filter':
                    class_alias('Hprose\\Filter\\JSONRPC\\ServiceFilter', 'Hprose_JSONRPC_Service_Filter');
                    break;
                case 'hprosexmlrpcclientfilter':
                    class_alias('Hprose\\Filter\\XMLRPC\\ClientFilter', 'HproseXMLRPCClientFilter');
                    break;
                case 'hprose_xmlrpc_clientfilter':
                    class_alias('Hprose\\Filter\\XMLRPC\\ClientFilter', 'Hprose_XMLRPC_ClientFilter');
                    break;
                case 'hprose_xmlrpc_client_filter':
                    class_alias('Hprose\\Filter\\XMLRPC\\ClientFilter', 'Hprose_XMLRPC_Client_Filter');
                    break;
                case 'hprosexmlrpcservicefilter':
                    class_alias('Hprose\\Filter\\XMLRPC\\ServiceFilter', 'HproseXMLRPCServiceFilter');
                    break;
                case 'hprose_xmlrpc_servicefilter':
                    class_alias('Hprose\\Filter\\XMLRPC\\ServiceFilter', 'Hprose_XMLRPC_ServiceFilter');
                    break;
                case 'hprose_xmlrpc_service_filter':
                    class_alias('Hprose\\Filter\\XMLRPC\\ServiceFilter', 'Hprose_XMLRPC_Service_Filter');
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
