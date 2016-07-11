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
 * Hprose/RealReaderRefer.php                             *
 *                                                        *
 * hprose RealReaderRefer class for php 5.3+              *
 *                                                        *
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

class RealReaderRefer implements ReaderRefer {
    private $ref;
    public function __construct() {
        $this->reset();
    }
    public function set($val) {
        $this->ref[] = $val;
    }
    public function read($index) {
        return $this->ref[$index];
    }
    public function reset() {
        $this->ref = array();
    }
}
