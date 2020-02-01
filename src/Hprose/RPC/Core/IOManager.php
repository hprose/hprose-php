<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Core/IOManager.php                            |
|                                                          |
| Hprose IOManager for PHP 7.1+                            |
|                                                          |
| LastModified: Jun 7, 2019                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

class IOManager extends HandlerManager {
    protected function getNextHandler(callable $handler, callable $next): callable {
        return function (string $request, Context $context) use ($handler, $next) {
            call_user_func($handler, $request, $context, $next);
        };
    }
}