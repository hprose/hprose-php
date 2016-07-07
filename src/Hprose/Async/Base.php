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
 * Hprose/Async/Base.php                                  *
 *                                                        *
 * base class of asynchronous functions for php 5.3+      *
 *                                                        *
 * LastModified: Jul 8, 2016                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Async;

class Base {
    protected function setTimer($func, $delay, $loop, $args) {
        throw new \Exception( "You need to install " .
                (php_sapi_name() == "cli" ? "swoole, " : "") .
                " event or libevent extension.");
    }
    protected function clearTimer($timer) {
        throw new \Exception( "You need to install " .
                (php_sapi_name() == "cli" ? "swoole, " : "") .
                " event or libevent extension.");
    }
    function loop() {}
    function nextTick($func) {
        $args = array_slice(func_get_args(), 1);
        call_user_func_array($func, $args);
    }
    function setInterval($func, $delay) {
        return $this->setTimer($func, $delay, true, array_slice(func_get_args(), 2));
    }
    function setTimeout($func, $delay = 0) {
        return $this->setTimer($func, $delay, false, array_slice(func_get_args(), 2));
    }
    function clearInterval($timer) {
        $this->clearTimer($timer);
    }
    function clearTimeout($timer) {
        $this->clearTimer($timer);
    }
}
