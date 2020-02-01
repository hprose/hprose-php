<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Core/InvokeManager.php                        |
|                                                          |
| Hprose InvokeManager for PHP 7.1+                        |
|                                                          |
| LastModified: Feb 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

class InvokeManager extends HandlerManager {
    protected function getNextHandler(callable $handler, callable $next): callable {
        return function (string $name, array &$args, Context $context) use ($handler, $next) {
            return call_user_func_array($handler, [$name, &$args, $context, $next]);
        };
    }
}