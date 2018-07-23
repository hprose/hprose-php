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
 * Hprose/Future/functions.php                            *
 *                                                        *
 * some helper functions for php 5.3+                     *
 *                                                        *
 * LastModified: Mar 7, 2017                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Future;

use Hprose\Future;
use Exception;
use Throwable;
use RangeException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionObject;

function isFuture($obj) {
    return $obj instanceof Future;
}

function error($e) {
    $future = new Future();
    $future->reject($e);
    return $future;
}

function value($v) {
    $future = new Future();
    $future->resolve($v);
    return $future;
}

function resolve($value) {
    return value($value);
}
function reject($reason) {
    return error($reason);
}

function sync($computation) {
    try {
        return toPromise(call_user_func($computation));
    }
    catch (UncatchableException $e) {
        throw $e->getPrevious();
    }
    catch (Exception $e) {
        return error($e);
    }
    catch (Throwable $e) {
        return error($e);
    }
}

function promise($executor) {
    $future = new Future();
    call_user_func($executor,
        function($value) use ($future) {
            $future->resolve($value);
        },
        function($reason) use ($future) {
            $future->reject($reason);
        }
    );
    return $future;
}

function toFuture($obj) {
    return isFuture($obj) ? $obj : value($obj);
}

function all($array) {
    return toFuture($array)->then(
        function($array) {
            $keys = array_keys($array);
            $n = count($array);
            $result = array();
            if ($n === 0) {
                return value($result);
            }
            $future = new Future();
            $resolve = function() use ($future, &$result, $keys) {
                $array = array();
                foreach($keys as $key) {
                    $array[$key] = $result[$key];
                }
                $future->resolve($array);
            };
            $reject = array($future, "reject");
            foreach ($array as $index => $element) {
                toFuture($element)->then(
                    function($value) use ($index, &$n, &$result, $resolve) {
                        $result[$index] = $value;
                        if (--$n === 0) {
                            $resolve();
                        }
                    },
                    $reject
                );
            }
            return $future;
        }
    );
}

function join() {
    return all(func_get_args());
}

function race($array) {
    return toFuture($array)->then(
        function($array) {
            $future = new Future();
            foreach ($array as $element) {
                toFuture($element)->fill($future);
            }
            return $future;
        }
    );
}

function any($array) {
    return toFuture($array)->then(
        function($array) {
            $keys = array_keys($array);
            $n = count($array);
            if ($n === 0) {
                throw new RangeException('any(): $array must not be empty');
            }
            $reasons = array();
            $future = new Future();
            $resolve = array($future, "resolve");
            $reject = function() use ($future, &$reasons, $keys) {
                $array = array();
                foreach($keys as $key) {
                    $array[$key] = $reasons[$key];
                }
                $future->reject($array);
            };
            foreach ($array as $index => $element) {
                toFuture($element)->then(
                    $resolve,
                    function($reason) use ($index, &$reasons, &$n, $reject) {
                        $reasons[$index] = $reason;
                        if (--$n === 0) {
                            $reject();
                        }
                    }
                );
            }
            return $future;
        }
    );
}

function settle($array) {
    return toFuture($array)->then(
        function($array) {
            $keys = array_keys($array);
            $n = count($array);
            $result = array();
            if ($n === 0) {
                return value($result);
            }
            $future = new Future();
            $resolve = function() use ($future, &$result, $keys) {
                $array = array();
                foreach($keys as $key) {
                    $array[$key] = $result[$key];
                }
                $future->resolve($array);
            };
            foreach ($array as $index => $element) {
                $f = toFuture($element);
                $f->whenComplete(
                    function() use ($index, $f, &$result, &$n, $resolve) {
                        $result[$index] = $f->inspect();
                        if (--$n === 0) {
                            $resolve();
                        }
                    }
                );
            }
            return $future;
        }
    );
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
    if (is_object($handler)) {
        if (is_callable($handler)) {
            if (class_exists("\\Generator") && ($handler instanceof \Generator)) {
                return co($handler);
            }
            return new CallableWrapper($handler);
        }
        return new Wrapper($handler);
    }
    if (is_callable($handler)) {
        if (class_exists("\\Generator")) {
            return function() use ($handler) {
                return all(func_get_args())->then(
                    function($args) use ($handler) {
                        return co(call_user_func_array($handler, $args));
                    }
                );
            };
        }
        return function() use ($handler) {
            return all(func_get_args())->then(
                function($args) use ($handler) {
                    return call_user_func_array($handler, $args);
                }
            );
        };
    }
    return $handler;
}

function each($array, $callback) {
    if (is_array($callback)) {
        $f = new ReflectionMethod($callback[0], $callback[1]);
    }
    else {
        $f = new ReflectionFunction($callback);
    }
    $n = $f->getNumberOfParameters();
    return all($array)->then(
        function($array) use ($n, $callback) {
            foreach ($array as $key => $value) {
                switch ($n) {
                    case 1: call_user_func($callback, $value); break;
                    case 2: call_user_func($callback, $value, $key); break;
                    default: call_user_func($callback, $value, $key, $array); break;
                }
            }
        }
    );
}

function every($array, $callback) {
    if (is_array($callback)) {
        $f = new ReflectionMethod($callback[0], $callback[1]);
    }
    else {
        $f = new ReflectionFunction($callback);
    }
    $n = $f->getNumberOfParameters();
    return all($array)->then(
        function($array) use ($n, $callback) {
            foreach ($array as $key => $value) {
                switch ($n) {
                    case 1: {
                        if (!call_user_func($callback, $value)) return false;
                        break;
                    }
                    case 2: {
                        if (!call_user_func($callback, $value, $key)) return false;
                        break;
                    }
                    default: {
                        if (!call_user_func($callback, $value, $key, $array)) return false;
                        break;
                    }
                }
            }
            return true;
        }
    );
}

function some($array, $callback) {
    if (is_array($callback)) {
        $f = new ReflectionMethod($callback[0], $callback[1]);
    }
    else {
        $f = new ReflectionFunction($callback);
    }
    $n = $f->getNumberOfParameters();
    return all($array)->then(
        function($array) use ($n, $callback) {
            foreach ($array as $key => $value) {
                switch ($n) {
                    case 1: {
                        if (call_user_func($callback, $value)) return true;
                        break;
                    }
                    case 2: {
                        if (call_user_func($callback, $value, $key)) return true;
                        break;
                    }
                    default: {
                        if (call_user_func($callback, $value, $key, $array)) return true;
                        break;
                    }
                }
            }
            return false;
        }
    );
}

function filter($array, $callback, $preserveKeys = false) {
    if (is_array($callback)) {
        $f = new ReflectionMethod($callback[0], $callback[1]);
    }
    else {
        $f = new ReflectionFunction($callback);
    }
    $n = $f->getNumberOfParameters();
    return all($array)->then(
        function($array) use ($n, $callback, $preserveKeys) {
            $result = array();
            $setResult = function($key, $value) use (&$result, $preserveKeys) {
                if ($preserveKeys) {
                    $result[$key] = $value;
                }
                else {
                    $result[] = $value;
                }
            };
            foreach ($array as $key => $value) {
                switch ($n) {
                    case 1: {
                        if (call_user_func($callback, $value)) {
                            $setResult($key, $value);
                        }
                        break;
                    }
                    case 2: {
                        if (call_user_func($callback, $value, $key)) {
                            $setResult($key, $value);
                        }
                        break;
                    }
                    default: {
                        if (call_user_func($callback, $value, $key, $array)) {
                            $setResult($key, $value);
                        }
                        break;
                    }
                }
            }
            return $result;
        }
    );
}

function map($array, $callback) {
    if (is_array($callback)) {
        $f = new ReflectionMethod($callback[0], $callback[1]);
    }
    else {
        $f = new ReflectionFunction($callback);
    }
    $n = $f->getNumberOfParameters();
    return all($array)->then(
        function($array) use ($n, $callback) {
            switch ($n) {
                case 1: return array_map($callback, $array);
                case 2: return array_map($callback, $array, array_keys($array));
                default: {
                    $result = array();
                    foreach ($array as $key => $value) {
                        $result[$key] = call_user_func($callback, $value, $key, $array);
                    }
                    return $result;
                }
            }
        }
    );
}

function reduce($array, $callback, $initial = NULL) {
    if ($initial !== NULL) {
        return all($array)->then(
            function($array) use ($callback, $initial) {
                $initial = toFuture($initial);
                return $initial->then(
                    function($initial) use ($array, $callback) {
                        return array_reduce($array, $callback, $initial);
                    }
                );
            }
        );
    }
    return all($array)->then(
        function($array) use ($callback) {
            return array_reduce($array, $callback);
        }
    );
}

function search($array, $searchElement, $strict = false) {
    return all($array)->then(
        function($array) use ($searchElement, $strict) {
            $searchElement = toFuture($searchElement);
            return $searchElement->then(
                function($searchElement) use ($array, $strict) {
                    return array_search($searchElement, $array, $strict);
                }
            );
        }
    );
}

function includes($array, $searchElement, $strict = false) {
    return all($array)->then(
        function($array) use ($searchElement, $strict) {
            $searchElement = toFuture($searchElement);
            return $searchElement->then(
                function($searchElement) use ($array, $strict) {
                    return in_array($searchElement, $array, $strict);
                }
            );
        }
    );
}

function diff(/*$array1, $array2, ...*/) {
    $args = func_get_args();
    for ($i = 0, $n = func_num_args(); $i < $n; ++$i) {
        $args[$i] = all($args[$i]);
    }
    return all($args)->then(
        function($array) {
            return call_user_func_array("array_diff", $array);
        }
    );
}

function udiff(/*$array1, $array2, $...*/) {
    $args = func_get_args();
    $callback = array_pop($args);
    for ($i = 0, $n = func_num_args() - 1; $i < $n; ++$i) {
        $args[$i] = all($args[$i]);
    }
    return all($args)->then(
        function($array) use ($callback) {
            array_push($array, $callback);
            return call_user_func_array("array_udiff", $array);
        }
    );
}

function promisify($fn) {
    return function() use ($fn) {
        $args = func_get_args();
        $future = new Future();
        $args[] = function() use ($future) {
            switch (func_num_args()) {
                case 0: $future->resolve(NULL); break;
                case 1: $future->resolve(func_get_arg(0)); break;
                default: $future->resolve(func_get_args()); break;
            }
        };
        try {
            call_user_func_array($fn, $args);
        }
        catch (\Exception $e) {
            $future->reject($e);
        }
        catch (\Throwable $e) {
            $future->reject($e);
        }
        return $future;
    };
}

if (class_exists("\\Generator")) {
    function toPromise($obj) {
        if (isFuture($obj)) {
            return $obj;
        }
        if ($obj instanceof \Generator) {
            return co($obj);
        }
        return value($obj);
    }

    function co($generator/*, arg1, arg2...*/) {
        if (is_callable($generator)) {
            $args = array_slice(func_get_args(), 1);
            $generator = call_user_func_array($generator, $args);
        }
        if (!($generator instanceof \Generator)) {
            return toFuture($generator);
        }
        $future = new Future();
        $onfulfilled = function($value) use (&$onfulfilled, &$onrejected, $generator, $future) {
            try {
                $next = $generator->send($value);
                if ($generator->valid()) {
                    toPromise($next)->then($onfulfilled, $onrejected);
                }
                else {
                    if (method_exists($generator, "getReturn")) {
                        $ret = $generator->getReturn();
                        $future->resolve(($ret === null) ? $value : $ret);
                    }
                    else {
                        $future->resolve($value);
                    }
                }
            }
            catch(\Exception $e) {
                $future->reject($e);
            }
            catch(\Throwable $e) {
                $future->reject($e);
            }
        };
        $onrejected = function($err) use (&$onfulfilled, $generator, $future) {
            try {
                $onfulfilled($generator->throw($err));
            }
            catch(\Exception $e) {
                $future->reject($e);
            }
            catch(\Throwable $e) {
                $future->reject($e);
            }
        };
        toPromise($generator->current())->then($onfulfilled, $onrejected);
        return $future;
    }
}
else {
    function toPromise($obj) {
        return toFuture($obj);
    }
}
