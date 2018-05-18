<?php
/**********************************************************\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: http://www.hprose.com/                 |
|                   http://www.hprose.org/                 |
|                                                          |
\**********************************************************/

/**********************************************************\
 *                                                        *
 * Hprose/Promise/functions.php                           *
 *                                                        *
 * some helper functions for php 5.3+                     *
 *                                                        *
 * LastModified: Dec 5, 2016                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Promise;

use Hprose\Future;

function isPromise($obj) {
    return $obj instanceof Future;
}

function error($e) {
    return Future\error($e);
}

function value($v, $context = null) {
    return Future\value($v, $context);
}

function resolve($value, $context = null) {
    return value($value, $context);
}
function reject($reason) {
    return error($reason);
}

function sync($computation) {
    return Future\sync($computation);
}

function promise($executor) {
    return new Promise($executor);
}

function all($array, $context = null) {
    return Future\all($array, $context);
}

function join() {
    return all(func_get_args());
}

function race($array, $context = null) {
    return Future\race($array, $context);
}

function any($array, $context = null) {
    return Future\any($array, $context);
}

function settle($array, $context = null) {
    return Future\settle($array, $context);
}

function run($handler/*, arg1, arg2, ... */) {
    $args = array_slice(func_get_args(), 1);
    return all($args)->then(
        function($args) use ($handler) {
            return call_user_func_array($handler, $args);
        }
    );
}

function wrap($handler, $context = null) {
    return Future\wrap($handler, $context);
}

function each($array, $callback) {
    return Future\each($array, $callback);
}

function every($array, $callback) {
    return Future\every($array, $callback);
}

function some($array, $callback) {
    return Future\some($array, $callback);
}

function filter($array, $callback, $preserveKeys = false) {
    return Future\filter($array, $callback, $preserveKeys);
}

function map($array, $callback) {
    return Future\map($array, $callback);
}

function reduce($array, $callback, $initial = NULL) {
    return Future\reduce($array, $callback, $initial);
}

function search($array, $searchElement, $strict = false) {
    return Future\search($array, $searchElement, $strict);
}

function includes($array, $searchElement, $strict = false) {
    return Future\includes($array, $searchElement, $strict);
}

function diff(/*$array1, $array2, ...*/) {
    return call_user_func_array("\\Hprose\\Future\\diff", func_get_args());
}

function udiff(/*$array1, $array2, $...*/) {
    return call_user_func_array("\\Hprose\\Future\\udiff", func_get_args());
}

function toPromise($obj, $context = null) {
    return Future\toPromise($obj, $context);
}

function promisify($fn, $context = null) {
    return Future\promisify($fn, $context);
}

if (class_exists("\\Generator")) {
    function co($generator, $context = null) {
        return \Hprose\Future\co($generator, $context);
    }
}
