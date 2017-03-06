<?php

use Hprose\Future;

$logHandler2 = function($request, stdClass $context, Closure $next) {
    error_log($request);
    $response = $next($request, $context);
    Future\run('error_log', $response);
    return $response;
};