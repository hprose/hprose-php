<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| ExecuteTimeout.php                                       |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Plugins;

use Hprose\RPC\Core\Context;
use Hprose\RPC\Core\TimeoutException;

class ExecuteTimeout {
    public $timeout; // second
    public function __construct(int $timeout = 30) {
        $this->timeout = $timeout;
    }
    public function handler(string $name, array &$args, Context $context, callable $next) {
        $timeout = $context->method->options['timeout'] ?? $this->timeout;
        $timeout = $timeout;
        if ($timeout > 0) {
            $async = pcntl_async_signals();
            try {
                pcntl_async_signals(true);
                pcntl_signal(SIGALRM, function () {
                    throw new TimeoutException('timeout');
                });
                pcntl_alarm($timeout);
                return $next($name, $args, $context);
            } finally {
                pcntl_alarm(0);
                pcntl_async_signals($async);
            }
        } else {
            return $next($name, $args, $context);
        }
    }
}