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
 * Hprose/Socket/Timer.php                                *
 *                                                        *
 * hprose socket Timer class for php 5.3+                 *
 *                                                        *
 * LastModified: Aug 10, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Socket;

class Timer {
    private $server;
    public function __construct($server) {
        $this->server = $server;
    }
    public function setTimeout($callback, $delay) {
        return $this->server->after($delay, $callback);
    }
    public function clearTimeout($timerid) {
        return $this->server->clear($timerid);
    }
    public function setImmediate($callback) {
        $this->server->defer($callback);
    }
}
