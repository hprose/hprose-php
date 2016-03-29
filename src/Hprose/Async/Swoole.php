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
 * LastModified: Mar 28, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Async;

require_once("Base.php");

class Swoole extends Base {
    const MILLISECONDS_PER_SECOND = 1000;
    private $n = 0;
    private function stopEvent() {
        $this->n--;
        if ($this->n === 0) {
            swoole_event_exit();
        }
    }
    protected function setEvent($func, $delay, $loop, $args) {
        $delay = $delay * self::MILLISECONDS_PER_SECOND;
        if ($delay === 0) {
            $delay = 1;
        }
        $this->n++;
        if ($loop) {
            $timer = swoole_timer_tick($delay, function() use($func, $args) {
                call_user_func_array($func, $args);
            });
        }
        else {
            $self = $this;
            $timer = swoole_timer_after($delay, function() use($self, $func, $args) {
                $self->stopEvent();
                call_user_func_array($func, $args);
            });
        }
        return $timer;
    }
    protected function clearEvent($timer) {
        if (@swoole_timer_clear($timer)) {
            $this->stopEvent();
        }
    }
    function loop() {
        swoole_event_wait();
    }
}
