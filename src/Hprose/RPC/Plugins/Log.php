<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Log.php                                                  |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Plugins;

use Hprose\RPC\Core\Context;
use Throwable;

class Log {
    public $enabled;
    public $message_type = 0;
    public $destination = null;
    public $extra_headers = null;
    public function __construct(bool $enabled = true) {
        $this->enabled = $enabled;
    }
    public function ioHandler(string $request, Context $context, callable $next): string {
        $enabled = $context['log'] ?? $this->enabled;
        if (!$enabled) {
            return $next($request, $context);
        }
        error_log(str_replace("\0", '\\0', $request), $this->message_type, $this->destination, $this->extra_headers);
        $response = $next($request, $context);
        error_log(str_replace("\0", '\\0', $response), $this->message_type, $this->destination, $this->extra_headers);
        return $response;
    }
    public function invokeHandler(string $name, array &$args, Context $context, callable $next) {
        $enabled = $context['log'] ?? $this->enabled;
        if (!$enabled) {
            return $next($name, $args, $context);
        }
        try {
            $a = $args;
            $result = $next($name, $args, $context);
            error_log("$name(" . substr(json_encode($a), 1, -1) . ') = ' . json_encode($result), $this->message_type, $this->destination, $this->extra_headers);
        } catch (Throwable $e) {
            error_log($e->getMessage() . "\n" . $e->getTraceAsString(), $this->message_type, $this->destination, $this->extra_headers);
            throw $e;
        }
        return $result;
    }
}