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
 * Hprose/FakeWriterRefer.php                             *
 *                                                        *
 * hprose FakeWriterRefer class for php 5.3+              *
 *                                                        *
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

class FakeWriterRefer implements WriterRefer {
    public function set($val) {}
    public function write(BytesIO $stream, $val) { return false; }
    public function reset() {}
}
