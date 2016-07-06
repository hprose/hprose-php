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
 * LastModified: Jul 5, 2016                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose {
    class Async {
        private static $async;
        private static function initSwoole() {
            include("Async/Swoole.php");
            self::$async = new Async\Swoole();
        }
        private static function initEvent() {
            require_once("Async/Event.php");
            self::$async = new Async\Event();
        }
        private static function initLibEvent() {
            require_once("Async/LibEvent.php");
            self::$async = new Async\LibEvent();
        }
        private static function initBase() {
            require_once("Async/Base.php");
            self::$async = new Async\Base();
        }
        /*
            $extention can be "swoole", "event" or "libevent"
        */
        static function init($extention) {
            switch ($extention) {
                case 'swoole':
                    if (extension_loaded("swoole")) {
                        if (php_sapi_name() != "cli") {
                            throw new \Exception("swoole extension only can be used in cli.");
                        }
                        self::initSwoole();
                    }
                    else {
                        throw new \Exception("You need to install swoole extension first.");
                    }
                    break;
                case 'event':
                    if (extension_loaded("event")) {
                        self::initEvent();
                    }
                    else {
                        throw new \Exception("You need to install event extension first.");
                    }
                    break;
                case 'libevent':
                    if (extension_loaded("libevent")) {
                        self::initLibEvent();
                    }
                    else {
                        throw new \Exception("You need to install libevent extension first.");
                    }
                    break;
                default:
                        throw new \Exception("You can only specify swoole, event or libevent.");
                    break;
            }
        }

        static function autoInit() {
            if (php_sapi_name() == "cli" && extension_loaded("swoole")) {
                self::initSwoole();
            }
            elseif (extension_loaded("event")) {
                self::initEvent();
            }
            elseif (extension_loaded("libevent")) {
                self::initLibEvent();
            }
            else {
                self::initBase();
            }
        }
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
    Async::autoInit();
    register_shutdown_function(array("\Hprose\Async", "loop"));
}