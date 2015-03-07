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
 * LastModified: Mar 7, 2015                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace {
    require('Hprose/Tags.php');
    require('Hprose/ResultMode.php');
    require('Hprose/BytesIO.php');
    require('Hprose/ClassManager.php');
    require('Hprose/Writer.php');
    require('Hprose/RawReader.php');
    require('Hprose/Reader.php');
    require('Hprose/Formatter.php');
    require('Hprose/Filter.php');
    require('Hprose/Client.php');
    require('Hprose/HttpClient.php');
    require('Hprose/Service.php');
    require('Hprose/HttpService.php');

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
    class_alias('Hprose\\HttpClient', 'HproseHttpClient');
    class_alias('Hprose\\Service', 'HproseService');
    class_alias('Hprose\\HttpService', 'HproseHttpService');
    class_alias('Hprose\\HttpServer', 'HproseHttpServer');

    function hprose_serialize($var, $simple = false) {
        return HproseFormatter::serialize($var, $simple);
    }

    function hprose_unserialize($data, $simple = false) {
        return HproseFormatter::unserialize($data, $simple);
    }

}
