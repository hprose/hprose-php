<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Plugins/ErrorToException.php                  |
|                                                          |
| LastModified: Mar 25, 2020                               |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Plugins;

use ErrorException;
use Hprose\RPC\Core\Context;

class ErrorToException {
    public $error_types;
    public function __construct(int $error_types = E_ALL | E_STRICT) {
        $this->error_types = $error_types;
    }
    public function handler(string $request, Context $context, callable $next): string {
        $error_handler = set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });
        var_dump($error_handler);
        try {
            return $next($request, $context);
        } finally {
            set_error_handler($error_handler);
        }
    }
}
