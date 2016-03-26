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
 * Hprose/Async.php                                       *
 *                                                        *
 * some asynchronous functions for php 5.3+               *
 *                                                        *
 * LastModified: Mar 26, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose {
    class Async {
        static $async;
        private static $queue = array();

        private static function drain() {
            for ($i = 0; isset(self::$queue[$i]); $i++) {
                call_user_func(self::$queue[$i]);
            }
            self::$queue = array();
        }

        static function nextTick($func) {
            $args = array_slice(func_get_args(), 1);
            $task = function() use ($func, $args) {
                call_user_func_array($func, $args);
            };
            $length = array_push(self::$queue, $task);
            if (1 !== $length) {
                return;
            }
            self::drain();
        }

        static function setInterval($func, $delay) {
            return call_user_func_array(array(self::$async, "setInterval"), func_get_args());
        }
        static function setTimeout($func, $delay = 0) {
            return call_user_func_array(array(self::$async, "setTimeout"), func_get_args());
        }
        static function clearInterval($timer) {
            self::$async->clearInterval($timer);
        }
        static function clearTimeout($timer) {
            self::$async->clearTimeout($timer);
        }
        static function loop() {
            self::$async->loop();
        }
    }
}

namespace {
    if (class_exists("EventBase") && class_exists("Event")) {
        require_once("Async/Event.php");
        Hprose\Async::$async = new Hprose\Async\Event();
    }
    elseif (function_exists("event_add")) {
        require_once("Async/LibEvent.php");
        Hprose\Async::$async = new Hprose\Async\LibEvent();
    }
    /*elseif (function_exists("swoole_timer_after") && function_exists("swoole_timer_tick")) {
        include("Async/Swoole.php");
        Hprose\Async::$async = new Hprose\Async\Swoole();
    }*/
    else {
        require_once("Async/Base.php");
        Hprose\Async::$async = new Hprose\Async\Base();
    }
    register_shutdown_function(array("Hprose\Async", "loop"));
}
