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
 * Hprose/BytesIO.php                                     *
 *                                                        *
 * hprose BytesIO class for php 5.3+                      *
 *                                                        *
 * LastModified: Jul 11, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

use Exception;

class BytesIO {
    protected $buffer;
    protected $length;
    protected $pos = 0;
    protected $mark = -1;
    public function __construct($string = '') {
        $this->buffer = $string;
        $this->length = strlen($string);
    }
    public function close() {
        $this->buffer = '';
        $this->pos = 0;
        $this->mark = -1;
        $this->length = 0;
    }
    public function length() {
        return $this->length;
    }
    public function getc() {
        if ($this->pos < $this->length) {
            return $this->buffer[$this->pos++];
        }
        return '';
    }
    public function read($n) {
        $s = substr($this->buffer, $this->pos, $n);
        $this->skip($n);
        return $s;
    }
    public function readfull() {
        $s = substr($this->buffer, $this->pos);
        $this->pos = $this->length;
        return $s;
    }
    public function readuntil($tag) {
        $pos = strpos($this->buffer, $tag, $this->pos);
        if ($pos !== false) {
            $s = substr($this->buffer, $this->pos, $pos - $this->pos);
            $this->pos = $pos + strlen($tag);
        }
        else {
            $s = substr($this->buffer, $this->pos);
            $this->pos = $this->length;
        }
        return $s;
    }
    public function readString($n) {
        $pos = $this->pos;
        $buffer = $this->buffer;
        for ($i = 0; $i < $n; ++$i) {
            switch (ord($buffer[$pos]) >> 4) {
                case 0:
                case 1:
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                case 7: {
                    // 0xxx xxxx
                    ++$pos;
                    break;
                }
                case 12:
                case 13: {
                    // 110x xxxx   10xx xxxx
                    $pos += 2;
                    break;
                }
                case 14: {
                    // 1110 xxxx  10xx xxxx  10xx xxxx
                    $pos += 3;
                    break;
                }
                case 15: {
                    // 1111 0xxx  10xx xxxx  10xx xxxx  10xx xxxx
                    $pos += 4;
                    ++$i;
                    if ($i >= $n) {
                        throw new Exception('bad utf-8 encoding');
                    }
                    break;
                }
                default: {
                    throw new Exception('bad utf-8 encoding');
                }
            }
        }
        return $this->read($pos - $this->pos);
    }
    public function mark() {
        $this->mark = $this->pos;
    }
    public function unmark() {
        $this->mark = -1;
    }
    public function reset() {
        if ($this->mark != -1) {
            $this->pos = $this->mark;
        }
    }
    public function skip($n) {
        $this->pos += $n;
    }
    public function eof() {
        return ($this->pos >= $this->length);
    }
    public function write($str, $n = -1) {
        if ($n == -1) {
            $this->buffer .= $str;
            $n = strlen($str);
        }
        else {
            $this->buffer .= substr($str, 0, $n);
        }
        $this->length += $n;
    }
    public function load($filename) {
        $str = file_get_contents($filename);
        if ($str === false) return false;
        $this->buffer = $str;
        $this->pos = 0;
        $this->mark = -1;
        $this->length = strlen($str);
        return true;
    }
    public function save($filename) {
        return file_put_contents($filename, $this->buffer);
    }
    public function toString() {
        return $this->buffer;
    }
    public function __toString() {
        return $this->buffer;
    }
}
