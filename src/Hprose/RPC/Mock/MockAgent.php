<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| MockAgent.php                                            |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Mock;

use Exception;

class MockAgent {
    private static $handlers = [];
    public static function register(string $address, callable $handler): void {
        self::$handlers[$address] = $handler;
    }
    public static function cancel(string $address): void {
        unset(self::$handlers[$address]);
    }
    public static function handler(string $address, string $request): string {
        $handler = self::$handlers[$address];
        if (isset($handler)) {
            return call_user_func($handler, $address, $request);
        }
        throw new Exception('Server is stopped');
    }
}