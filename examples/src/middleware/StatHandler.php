<?php

class StatHandler {
    private $message;
    public function __construct($message) {
        $this->message = $message;
    }
    public function asynchandle($name, array &$args, stdClass $context, Closure $next) {
        $start = microtime(true);
        yield $next($name, $args, $context);
        $end = microtime(true);
        error_log($this->message . ': It takes ' . ($end - $start) . ' s.');
    }
    public function synchandle($name, array &$args, stdClass $context, Closure $next) {
        $start = microtime(true);
        $response = $next($name, $args, $context);
        $end = microtime(true);
        error_log($this->message . ': It takes ' . ($end - $start) . ' s.');
        return $response;
    }
}
