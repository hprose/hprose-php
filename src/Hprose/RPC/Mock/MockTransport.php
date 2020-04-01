<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| MockTransport.php                                        |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Mock;

use Hprose\RPC\Core\Context;
use Hprose\RPC\Core\TimeoutException;
use Hprose\RPC\Core\Transport;

class MockTransport implements Transport {
    public static $schemes = ['mock'];
    public function transport(string $request, Context $context): string {
        $uri = parse_url($context->uri);
        $timeout = $context->timeout;
        if ($timeout > 0) {
            $async = pcntl_async_signals();
            try {
                pcntl_async_signals(true);
                pcntl_signal(SIGALRM, function () {
                    throw new TimeoutException('timeout');
                });
                pcntl_alarm($timeout);
                return MockAgent::handler($uri['host'], $request);
            } finally {
                pcntl_alarm(0);
                pcntl_async_signals($async);
            }
        } else {
            return MockAgent::handler($uri['host'], $request);
        }
    }
    public function abort(): void {}
}
