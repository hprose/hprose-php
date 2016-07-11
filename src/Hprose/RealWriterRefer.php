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
 * Hprose/RealWriterRefer.php                             *
 *                                                        *
 * hprose RealWriterRefer class for php 5.3+              *
 *                                                        *
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

use SplObjectStorage;

class RealWriterRefer implements WriterRefer {
    private $oref;
    private $sref = array();
    private $refcount = 0;

    public function __construct() {
        $this->oref = new SplObjectStorage();
    }

    private function writeRef(BytesIO $stream, $index) {
        $stream->write(Tags::TagRef . $index . Tags::TagSemicolon);
        return true;
    }
    public function set($val) {
        if (is_string($val)) {
            $this->sref[$val] = $this->refcount;
        }
        elseif (is_object($val)) {
            $this->oref->attach($val, $this->refcount);
        }
        $this->refcount++;
    }
    public function write(BytesIO $stream, $val) {
        if (is_string($val) && isset($this->sref[$val])) {
            return $this->writeRef($stream, $this->sref[$val]);
        }
        elseif (is_object($val) && isset($this->oref[$val])) {
            return $this->writeRef($stream, $this->oref[$val]);
        }
        return false;
    }
    public function reset() {
        $this->oref = new \SplObjectStorage();
        $this->sref = array();
        $this->refcount = 0;
    }
}
