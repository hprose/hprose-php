<?php

class SizeHandler {
    private $message;
    public function __construct($message) {
        $this->message = $message;
    }
    public function asynchandle($request, stdClass $context, Closure $next) {
        error_log($this->message . ' request size: ' . strlen($request));
        $response = (yield $next($request, $context));
        error_log($this->message . ' response size: ' . strlen($response));
    }
    public function synchandle($request, stdClass $context, Closure $next) {
        error_log($this->message . ' request size: ' . strlen($request));
        $response = $next($request, $context);
        error_log($this->message . ' response size: ' . strlen($response));
        return $response;
    }
}
