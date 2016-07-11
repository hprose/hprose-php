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
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Future;

use Hprose\Future;
use Exception;
use Throwable;
use RangeException;

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
            $onfulfilled = function($index) use ($future, &$result, &$n, $keys) {
                return function($value) use ($index, $future, &$result, &$n, $keys) {
                    $result[$index] = $value;
                    if (--$n === 0) {
                        $array = array();
                        foreach($keys as $key) {
                            $array[$key] = $result[$key];
                        }
                        $future->resolve($array);
                    }
                };
            };
            $onrejected = array($future, "reject");
            foreach ($array as $index => $element) {
                toFuture($element)->then($onfulfilled($index), $onrejected);
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
            $onfulfilled = array($future, "resolve");
            $onrejected = function($index) use ($future, &$reasons, &$n, $keys) {
                return function($reason) use ($index, $future, &$reasons, &$n, $keys) {
                    $reasons[$index] = $reason;
                    if (--$n === 0) {
                        $array = array();
                        foreach($keys as $key) {
                            $array[$key] = $reasons[$key];
                        }
                        $future->reject($array);
                    }
                };
            };
            foreach ($array as $index => $element) {
                $f = toFuture($element);
                $f->then($onfulfilled, $onrejected($index));
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
            $oncomplete = function($index, $f) use ($future, &$result, &$n, $keys) {
                return function() use ($index, $f, $future, &$result, &$n, $keys) {
                    $result[$index] = $f->inspect();
                    if (--$n === 0) {
                        $array = array();
                        foreach($keys as $key) {
                            $array[$key] = $result[$key];
                        }
                        $future->resolve($array);
                    }
                };
            };
            foreach ($array as $index => $element) {
                $f = toFuture($element);
                $f->whenComplete($oncomplete($index, $f));
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
            return new CallableWrapper($handler);
        }
        return new Wrapper($handler);
    }
    if (is_callable($handler)) {
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
    return all($array)->then(
        function($array) use ($callback) {
            foreach ($array as $key => $value) {
                call_user_func($callback, $value, $key, $array);
            }
        }
    );
}

function every($array, $callback) {
    return all($array)->then(
        function($array) use ($callback) {
            foreach ($array as $key => $value) {
                if (!call_user_func($callback, $value, $key, $array)) return false;
            }
            return true;
        }
    );
}

function some($array, $callback) {
    return all($array)->then(
        function($array) use ($callback) {
            foreach ($array as $key => $value) {
                if (call_user_func($callback, $value, $key, $array)) return true;
            }
            return false;
        }
    );
}

function filter($array, $callback, $preserveKeys = false) {
    return all($array)->then(
        function($array) use ($callback, $preserveKeys) {
            $result = array();
            foreach ($array as $key => $value) {
                if (call_user_func($callback, $value, $key, $array)) {
                    if ($preserveKeys) {
                        $result[$key] = $value;
                    }
                    else {
                        $result[] = $value;
                    }
                }
            }
            return $result;
        }
    );
}

function map($array, $callback) {
    return all($array)->then(
        function($array) use ($callback) {
            return array_map($callback, $array);
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

function toPromise($obj) {
    if (isFuture($obj)) return $obj;
    if (class_exists("\\Generator") && ($obj instanceof \Generator)) return co($obj);
    if (is_array($obj)) return arrayToPromise($obj);
    if (is_object($obj)) return objectToPromise($obj);
    return value($obj);
}

function arrayToPromise(array $array) {
    return all(array_map("\\Hprose\\Future\\toPromise", $array));
}

function objectToPromise($obj) {
    $result = clone $obj;
    $values = array();
    foreach ($result as $key => $value) {
        $values[] = toPromise($value)->then(function($v) use ($result, $key) {
            $result->$key = $v; 
        });
    }
    return all($values)->then(function() use ($result) {
        return $result;
    });
}

if (class_exists("\\Generator")) {
    function co($generator/*, arg1, arg2...*/) {
        if (is_callable($generator)) {
            $args = array_slice(func_get_args(), 1);
            $generator = call_user_func_array($generator, $args);
        }
        if (!($generator instanceof \Generator)) {
            return value($generator);
        }
        $next = function($yield) use ($generator, &$next) {
            if ($generator->valid()) {
                return toPromise($yield)->then(function($value) use ($generator, &$next) {
                    $yield = $generator->send($value);
                    if ($generator->valid()) {
                        return $next($yield);
                    }
                    if (method_exists($generator, "getReturn")) {
                        return $generator->getReturn();
                    }
                    return $value;
                },
                function($e) use ($generator, &$next) {
                    return $next($generator->throw($e));
                });
            }
            else {
                if (method_exists($generator, "getReturn")) {
                    return value($generator->getReturn());
                }
                else {
                    return value(null);
                }
            }
        };
        return $next($generator->current());
    }
}
