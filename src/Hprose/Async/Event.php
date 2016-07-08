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
 * asynchronous functions base on event for php 5.3+      *
 *                                                        *
 * LastModified: Jul 8, 2016                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Async;

class Event extends Base {
    private $eventbase;
    public function __construct() {
        $this->eventbase = new \EventBase();
    }
    protected function setTimer($func, $delay, $loop, $args) {
        $e = \Event::timer($this->eventbase, function() use($func, $delay, $loop, $args, &$e) {
            $e->delTimer();
            if ($loop) {
                $e->addTimer($delay);
            }
            call_user_func_array($func, $args);
        });
        $e->addTimer($delay);
        return $e;
    }
    protected function clearTimer($timer) {
        $timer->free();
    }
    function loop() {
        @$this->eventbase->loop();
    }
}
