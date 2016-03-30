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
 * Hprose/Async/Libuv.php                                 *
 *                                                        *
 * asynchronous functions base on uv for hhvm             *
 *                                                        *
 * LastModified: Mar 26, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Async;

require_once("Base.php");

class Libuv extends Base {
    private $uvloop;
    const MILLISECONDS_PER_SECOND = 1000;
    public function __construct() {
        $this->uvloop = uv_default_loop();
    }
    protected function setEvent($func, $delay, $loop, $args) {
        $delay *= self::MICROSECONDS_PER_SECOND;
        $timer = uv_timer_init($this->uvloop);
        uv_timer_start($timer, $delay, $loop ? $delay : 0, function() use($func, $args) {
            call_user_func_array($func, $args);
        });
        return $e;
    }
    protected function clearEvent($timer) {
        uv_timer_stop($timer);
    }
    function loop() {
        uv_run();
    }
}
