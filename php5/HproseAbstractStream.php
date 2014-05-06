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
 * HproseAbstractStream.php                               *
 *                                                        *
 * hprose abstract stream class for php5.                 *
 *                                                        *
 * LastModified: Jan 2, 2014                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

abstract class HproseAbstractStream {
    public abstract function close();
    public abstract function getc();
    public abstract function read($length);
    public abstract function readuntil($char);
    public abstract function seek($offset, $whence = SEEK_SET);
    public abstract function mark();
    public abstract function unmark();
    public abstract function reset();
    public abstract function skip($n);
    public abstract function eof();
    public abstract function write($string, $length = -1);
}