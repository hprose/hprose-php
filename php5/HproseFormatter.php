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
 * HproseFormatter.php                                    *
 *                                                        *
 * hprose formatter library for php5.                     *
 *                                                        *
 * LastModified: Jul 12, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (!extension_loaded('hprose')) {

require_once('HproseIOStream.php');
// require_once('HproseWriter.php');
// require_once('HproseReader.php');
require_once('HproseSerialize.php');
require_once('HproseUnserialize.php');

class HproseFormatter {
    public static function serialize($var, $simple = false) {
        return hprose_serialize($var, $simple);
        // $stream = new HproseStringStream();
        // $hproseWriter = new HproseWriter($stream, $simple);
        // $hproseWriter->serialize($var);
        // return $stream->toString();
    }
    public static function &unserialize($data, $simple = false) {
        return hprose_unserialize($data, $simple);
        // $stream = new HproseStringStream($data);
        // $hproseReader = new HproseReader($stream, $simple);
        // return $hproseReader->unserialize();
    }
}

} // endif (!extension_loaded('hprose'))
?>