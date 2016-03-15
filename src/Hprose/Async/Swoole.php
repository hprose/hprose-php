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
 * Hprose/Async/Swoole.php                                *
 *                                                        *
 * asynchronous functions base on swoole for php 5.3+     *
 *                                                        *
 * LastModified: Mar 13, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Async;

require_once("Base.php");

class Swoole extends Base {
    const MILLISECONDS_PER_SECOND = 1000;
    public function __construct() {
    }
    protected function setEvent($func, $delay, $loop, $args) {
        $delay = $delay * self::MILLISECONDS_PER_SECOND;
        if ($loop) {
            $timer = swoole_timer_tick($delay, function() use($func, $args) {
                call_user_func_array($func, $args);
            });
        }
        else {
            $timer = swoole_timer_after($delay, function() use($func, $args) {
                call_user_func_array($func, $args);
            });
        }
        return $timer;
    }
    protected function clearEvent($timer) {
        swoole_timer_clear($timer);
    }
    function loop() {}
}
