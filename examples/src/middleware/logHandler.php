<?php

use Hprose\Future;

$logHandler = function($name, array &$args, stdClass $context, Closure $next) {
    error_log("before invoke:");
    error_log($name);
    error_log(var_export($args, true));
    $result = $next($name, $args, $context);
    error_log("after invoke:");
    if (Future\isFuture($result)) {
        $result->then(function($result) {
            error_log(var_export($result, true));
        });
    }
    else {
        error_log(var_export($result, true));
    }
    return $result;
};