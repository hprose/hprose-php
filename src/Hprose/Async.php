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
        static function nextTick($func) {
            $args = array_slice(func_get_args(), 1);
            $task = function() use ($func, $args) {
                call_user_func_array($func, $args);
            };
            self::setTimeout($task, 0);
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
    if (function_exists("swoole_timer_after") && function_exists("swoole_timer_tick")) {
        include("Async/Swoole.php");
        Hprose\Async::$async = new Hprose\Async\Swoole();
    }
    elseif (class_exists("EventBase") && class_exists("Event")) {
        require_once("Async/Event.php");
        Hprose\Async::$async = new Hprose\Async\Event();
    }
    elseif (function_exists("event_add")) {
        require_once("Async/LibEvent.php");
        Hprose\Async::$async = new Hprose\Async\LibEvent();
    }
    else {
        require_once("Async/Base.php");
        Hprose\Async::$async = new Hprose\Async\Base();
    }
    register_shutdown_function(array("Hprose\Async", "loop"));
}
