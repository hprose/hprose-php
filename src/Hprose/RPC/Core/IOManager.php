<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| IOManager.php                                            |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

class IOManager extends PluginManager {
    protected function getNextHandler(callable $handler, callable $next): callable {
        return function (string $request, Context $context) use ($handler, $next) {
            return call_user_func($handler, $request, $context, $next);
        };
    }
}