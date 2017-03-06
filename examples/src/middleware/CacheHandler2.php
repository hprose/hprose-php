<?php

class CacheHandler2 {
    private $cache = array();
    function handle($request, stdClass $context, Closure $next) {
        if (isset($context->userdata->cache)) {
            if (isset($this->cache[$request])) {
                return $this->cache[$request];
            }
            $response = $next($request, $context);
            $this->cache[$request] = $response;
            return $response;
        }
        return $next($request, $context);
    }
}
