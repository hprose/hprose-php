<?php

class StatHandler2 {
    private $message;
    public function __construct($message) {
        $this->message = $message;
    }
    public function asynchandle($request, stdClass $context, Closure $next) {
        $start = microtime(true);
        yield $next($request, $context);
        $end = microtime(true);
        error_log($this->message . ': It takes ' . ($end - $start) . ' s.');
    }
    public function synchandle($request, stdClass $context, Closure $next) {
        $start = microtime(true);
        $response = $next($request, $context);
        $end = microtime(true);
        error_log($this->message . ': It takes ' . ($end - $start) . ' s.');
        return $response;
    }
}
