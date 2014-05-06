<?php
/**********************************************************\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: http://www.hprose.com/                 |
|                   http://www.hprose.net/                 |
|                   http://www.hprose.org/                 |
|                                                          |
\**********************************************************/

/**********************************************************\
 *                                                        *
 * HproseFormatter.php                                    *
 *                                                        *
 * hprose formatter library for php5.                     *
 *                                                        *
 * LastModified: Feb 11, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

require_once('HproseIOStream.php');
require_once('HproseReader.php');
require_once('HproseWriter.php');

class HproseFormatter {
    public static function serialize(&$var, $simple = false) {
        $stream = new HproseStringStream();
        $hproseWriter = new HproseWriter($stream, $simple);
        $hproseWriter->serialize($var);
        return $stream->toString();
    }
    public static function &unserialize($data, $simple = false) {
        $stream = new HproseStringStream($data);
        $hproseReader = new HproseReader($stream, $simple);
        return $hproseReader->unserialize();
    }
}
