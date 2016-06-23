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
 * LastModified: Jun 23, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Async;

class Base {
    protected function setEvent($func, $delay, $loop, $args) {
        throw new \Exception( "You need to install " .
                (php_sapi_name() == "cli" ? "swoole, " : "") .
                " event or libevent extension.");
    }
    protected function clearEvent($timer) {
        throw new \Exception( "You need to install " .
                (php_sapi_name() == "cli" ? "swoole, " : "") .
                " event or libevent extension.");
    }
    function loop() {}
    function setInterval($func, $delay) {
        return $this->setEvent($func, $delay, true, array_slice(func_get_args(), 2));
    }
    function setTimeout($func, $delay = 0) {
        return $this->setEvent($func, $delay, false, array_slice(func_get_args(), 2));
    }
    function clearInterval($timer) {
        $this->clearEvent($timer);
    }
    function clearTimeout($timer) {
        $this->clearEvent($timer);
    }
}
