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
 * Hprose/FakeReaderRefer.php                             *
 *                                                        *
 * hprose FakeReaderRefer class for php 5.3+              *
 *                                                        *
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

use Exception;

class FakeReaderRefer implements ReaderRefer {
    public function set($val) {}
    public function read($index) {
        throw new Exception("Unexpected serialize tag '" .
                            Tags::TagRef .
                            "' in stream");
    }
    public function reset() {}
}
