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
 * HproseStringStream.php                                 *
 *                                                        *
 * hprose string stream class for php5.                   *
 *                                                        *
 * LastModified: Jul 12, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (!extension_loaded('hprose')) {

class HproseStringStream {
    protected $buffer;
    protected $pos;
    protected $mark;
    protected $length;
    public function __construct($string = '') {
        $this->init($string);
    }
    public function init($string) {
        $this->buffer = $string;
        $this->pos = 0;
        $this->mark = -1;
        $this->length = strlen($string);
    }
    public function close() {
        $this->buffer = NULL;
        $this->pos = 0;
        $this->mark = -1;
        $this->length = 0;
    }
    public function length() {
        return $this->length;
    }
    public function getc() {
        return $this->buffer{$this->pos++};
    }
    public function read($length) {
        $s = substr($this->buffer, $this->pos, $length);
        $this->skip($length);
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
    public function seek($offset, $whence = SEEK_SET) {
        switch ($whence) {
            case SEEK_SET:
                $this->pos = $offset;
                break;
            case SEEK_CUR:
                $this->pos += $offset;
                break;
            case SEEK_END:
                $this->pos = $this->length + $offset;
                break;
        }
        $this->mark = -1;
        return 0;
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
    public function write($string, $length = -1) {
        if ($length == -1) {
            $this->buffer .= $string;
            $length = strlen($string);
        }
        else {
            $this->buffer .= substr($string, 0, $length);
        }
        $this->length += $length;
    }
    public function toString() {
        return $this->buffer;
    }
    public function __toString() {
        return $this->buffer;
    }
}

} // endif (!extension_loaded('hprose'))
?>