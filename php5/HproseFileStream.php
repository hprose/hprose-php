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
 * HproseFileStream.php                                   *
 *                                                        *
 * hprose file stream class for php5.                     *
 *                                                        *
 * LastModified: Jun 23, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

class HproseFileStream {
    protected $fp;
    protected $buf;
    protected $unmark;
    protected $pos;
    protected $length;
    public function __construct($fp) {
        $this->fp = $fp;
        $this->buf = "";
        $this->unmark = true;
        $this->pos = -1;
        $this->length = 0;
    }
    public function close() {
        return fclose($this->fp);
    }
    public function getc() {
        if ($this->pos == -1) {
            return fgetc($this->fp);
        }
        elseif ($this->pos < $this->length) {
            return $this->buf{$this->pos++};
        }
        elseif ($this->unmark) {
            $this->buf = "";
            $this->pos = -1;
            $this->length = 0;
            return fgetc($this->fp);
        }
        elseif (($c = fgetc($this->fp)) !== false) {
            $this->buf .= $c;
            $this->pos++;
            $this->length++;
        }
        return $c;
    }
    public function read($length) {
        if ($this->pos == -1) {
            return fread($this->fp, $length);
        }
        elseif ($this->pos < $this->length) {
            $len = $this->length - $this->pos;
            if ($len < $length) {
                $s = fread($this->fp, $length - $len);
                $this->buf .= $s;
                $this->length += strlen($s);
            }
            $s = substr($this->buf, $this->pos, $length);
            $this->pos += strlen($s);
        }
        elseif ($this->unmark) {
            $this->buf = "";
            $this->pos = -1;
            $this->length = 0;
            return fread($this->fp, $length);
        }
        elseif (($s = fread($this->fp, $length)) !== "") {
            $this->buf .= $s;
            $len = strlen($s);
            $this->pos += $len;
            $this->length += $len;
        }
        return $s;
    }
    public function readuntil($char) {
        $s = '';
        while ((($c = $this->getc()) != $char) && $c !== false) $s .= $c;
        return $s;
    }
    public function seek($offset, $whence = SEEK_SET) {
        if (fseek($this->fp, $offset, $whence) == 0) {
            $this->buf = "";
            $this->unmark = true;
            $this->pos = -1;
            $this->length = 0;
            return 0;
        }
        return -1;
    }
    public function mark() {
        $this->unmark = false;
        if ($this->pos == -1) {
            $this->buf = "";
            $this->pos = 0;
            $this->length = 0;
        }
        elseif ($this->pos > 0) {
            $this->buf = substr($this->buf, $this->pos);
            $this->length -= $this->pos;
            $this->pos = 0;
        }
    }
    public function unmark() {
        $this->unmark = true;
    }
    public function reset() {
        $this->pos = 0;
    }
    public function skip($n) {
        $this->read($n);
    }
    public function eof() {
        if (($this->pos != -1) && ($this->pos < $this->length)) return false;
        return feof($this->fp);
    }
    public function write($string, $length = -1) {
        if ($length == -1) $length = strlen($string);
        return fwrite($this->fp, $string, $length);
    }
}

?>