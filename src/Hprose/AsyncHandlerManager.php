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
 * Hprose/AsyncHandlerManager.php                         *
 *                                                        *
 * hprose AsyncHandlerManager class for php 5.3+          *
 *                                                        *
 * LastModified: Jul 10, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose {
    abstract class AsyncHandlerManager extends HandlerManager {
        protected function getNextInvokeHandler($next, $handler) {
            return function($name, array $args, \stdClass $context) use ($next, $handler) {
                    try {
                        return Future\toPromise(call_user_func($handler, $name, $args, $context, $next));
                    }
                    catch (\Exception $e) {
                        return Future\error($e);
                    }
                    catch (\Throwable $e) {
                        return Future\error($e);
                    }
            };
        }
        protected function getNextFilterHandler($next, $handler) {
            return function($request, \stdClass $context) use ($next, $handler) {
                try {
                    return Future\toPromise(call_user_func($handler, $request, $context, $next));
                }
                catch (\Exception $e) {
                    return Future\error($e);
                }
                catch (\Throwable $e) {
                    return Future\error($e);
                }
            };
        }
    }
}