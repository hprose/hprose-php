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
 * Hprose/Async/Event.php                                 *
 *                                                        *
 * asynchronous functions base on libevent for php 5.3+   *
 *                                                        *
 * LastModified: Mar 26, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Async;

require_once("Base.php");

class LibEvent extends Base {
    private $eventbase;
    const MICROSECONDS_PER_SECOND = 1000000;
    public function __construct() {
        $this->eventbase = event_base_new();
    }
    protected function setEvent($func, $delay, $loop, $args) {
        $delay *= self::MICROSECONDS_PER_SECOND;
        $e = event_new();
        event_set($e, 0, EV_TIMEOUT, function() use($func, $delay, $loop, $args, $e) {
            if ($loop) {
                event_add($e, $delay);
            }
            call_user_func_array($func, $args);
        });
        event_base_set($e, $this->eventbase);
        event_add($e, $delay);
        return $e;
    }
    protected function clearEvent($timer) {
        event_del($timer);
        event_free($timer);
    }
    function loop() {
        event_base_loop($this->eventbase);
    }
}
