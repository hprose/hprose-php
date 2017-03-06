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
 * Hprose/Formatter.php                                   *
 *                                                        *
 * hprose formatter class for php 5.3+                    *
 *                                                        *
 * LastModified: Jul 16, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

class Formatter {
    public static function serialize($var, $simple = false) {
        $stream = new BytesIO();
        $writer = new Writer($stream, $simple);
        $writer->serialize($var);
        $data = $stream->toString();
        $stream->close();
        return $data;
    }
    public static function unserialize($data, $simple = false) {
        $stream = new BytesIO($data);
        $reader = new Reader($stream, $simple);
        $result = $reader->unserialize();
        $stream->close();
        return $result;
    }
}
