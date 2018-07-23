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

function value($v) {
    return Future\value($v);
}

function resolve($value) {
    return value($value);
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

function all($array) {
    return Future\all($array);
}

function join() {
    return all(func_get_args());
}

function race($array) {
    return Future\race($array);
}

function any($array) {
    return Future\any($array);
}

function settle($array) {
    return Future\settle($array);
}

function run($handler/*, arg1, arg2, ... */) {
    $args = array_slice(func_get_args(), 1);
    return all($args)->then(
        function($args) use ($handler) {
            return call_user_func_array($handler, $args);
        }
    );
}

function wrap($handler) {
    return Future\wrap($handler);
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

function toPromise($obj) {
    return Future\toPromise($obj);
}

function promisify($fn) {
    return Future\promisify($fn);
}

if (class_exists("\\Generator")) {
    function co(/*$generator, arg1, arg2...*/) {
        return call_user_func_array("\\Hprose\\Future\\co", func_get_args());
    }
}
