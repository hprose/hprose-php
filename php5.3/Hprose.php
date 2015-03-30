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
 * LastModified: Mar 29, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace {
if (!extension_loaded('hprose')) {
    require(dirname(__FILE__).'/Hprose/Tags.php');
    require(dirname(__FILE__).'/Hprose/ResultMode.php');
    require(dirname(__FILE__).'/Hprose/BytesIO.php');
    require(dirname(__FILE__).'/Hprose/ClassManager.php');
    require(dirname(__FILE__).'/Hprose/Writer.php');
    require(dirname(__FILE__).'/Hprose/RawReader.php');
    require(dirname(__FILE__).'/Hprose/Reader.php');
    require(dirname(__FILE__).'/Hprose/Formatter.php');
    require(dirname(__FILE__).'/Hprose/Filter.php');
    require(dirname(__FILE__).'/Hprose/Client.php');
    require(dirname(__FILE__).'/Hprose/Service.php');

    class_alias('Hprose\\Tags', 'HproseTags');
    class_alias('Hprose\\ResultMode', 'HproseResultMode');
    class_alias('Hprose\\BytesIO', 'HproseBytesIO');
    class_alias('Hprose\\ClassManager', 'HproseClassManager');
    class_alias('Hprose\\Writer', 'HproseWriter');
    class_alias('Hprose\\RawReader', 'HproseRawReader');
    class_alias('Hprose\\Reader', 'HproseReader');
    class_alias('Hprose\\Formatter', 'HproseFormatter');
    class_alias('Hprose\\Filter', 'HproseFilter');
    class_alias('Hprose\\Client', 'HproseClient');
    class_alias('Hprose\\Service', 'HproseService');

    function hprose_serialize($var, $simple = false) {
        return HproseFormatter::serialize($var, $simple);
    }

    function hprose_unserialize($data, $simple = false) {
        return HproseFormatter::unserialize($data, $simple);
    }
}
    require(dirname(__FILE__).'/Hprose/HttpClient.php');
    require(dirname(__FILE__).'/Hprose/HttpService.php');
    require(dirname(__FILE__).'/Hprose/SwooleHttpService.php');

    class_alias('Hprose\\HttpClient', 'HproseHttpClient');
    class_alias('Hprose\\HttpService', 'HproseHttpService');
    class_alias('Hprose\\HttpServer', 'HproseHttpServer');
    class_alias('Hprose\\SwooleHttpService', 'HproseSwooleHttpService');
    class_alias('Hprose\\SwooleHttpServer', 'HproseSwooleHttpServer');
}
