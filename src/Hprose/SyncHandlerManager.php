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
 * Hprose/SyncHandlerManager.php                          *
 *                                                        *
 * hprose SyncHandlerManager class for php 5.3+           *
 *                                                        *
 * LastModified: Jul 10, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose {
    abstract class SyncHandlerManager extends HandlerManager {
        protected function getNextInvokeHandler($next, $handler) {
            return function($name, array $args, \stdClass $context) use ($next, $handler) {
                return call_user_func($handler, $name, $args, $context, $next);
            };
        }
        protected function getNextFilterHandler($next, $handler) {
            return function($request, \stdClass $context) use ($next, $handler) {
                return call_user_func($handler, $request, $context, $next);
            };
        }
    }
}