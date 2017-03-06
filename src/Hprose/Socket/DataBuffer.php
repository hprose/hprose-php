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
 * Hprose/Socket/DataBuffer.php                           *
 *                                                        *
 * hprose socket DataBuffer class for php 5.3+            *
 *                                                        *
 * LastModified: Jul 12, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Socket;

class DataBuffer {
    public $index;
    public $buffer;
    public $length;
    public $id;
    public function __construct($index, $buffer, $length, $id = null) {
        $this->index = $index;
        $this->buffer = $buffer;
        $this->length = $length;
        $this->id = $id;
    }
}
