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

class InvokeManager extends PluginManager {
    protected function getNextPlugin(callable $plugin, callable $next): callable {
        return function (string $name, array &$args, Context $context) use ($plugin, $next) {
            return call_user_func_array($plugin, [$name, &$args, $context, $next]);
        };
    }
}