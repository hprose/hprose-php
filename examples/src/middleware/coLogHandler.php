<?php

$coLogHandler = function($name, array &$args, stdClass $context, Closure $next) {
    error_log("before invoke:");
    error_log($name);
    error_log(var_export($args, true));
    $result = (yield $next($name, $args, $context));
    error_log("after invoke:");
    error_log(var_export($result, true));
};
