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
 * Hprose/functions.php                                   *
 *                                                        *
 * some helper functions for php 5.3+                     *
 *                                                        *
 * LastModified: Mar 28, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose {
    if (PHP_MAJOR_VERSION < 7) {
        function trycatch($try, $catch) {
            try {
                return call_user_func($try);
            }
            catch(\Exception $e) {
                return call_user_func($catch, $e);
            }
        }
    }
    else {
        function trycatch($try, $catch) {
            try {
                return call_user_func($try);
            }
            catch(\Throwable $e) {
                return call_user_func($catch, $e);
            }
        }
    }
    function nextTick($func) {
        return call_user_func_array(array("\\Hprose\\Async", "nextTick"), func_get_args());
    }
    function setInterval($func, $delay) {
        return call_user_func_array(array("\\Hprose\\Async", "setInterval"), func_get_args());
    }
    function setTimeout($func, $delay = 0) {
        return call_user_func_array(array("\\Hprose\\Async", "setTimeout"), func_get_args());
    }
    function clearInterval($timer) {
        Async::clearInterval($timer);
    }
    function clearTimeout($timer) {
        Async::clearTimeout($timer);
    }
    function loop() {
        Async::loop();
    }
    function deferred() {
        return new Deferred();
    }
}

namespace Hprose\Future {
    function isFuture($obj) {
        return $obj instanceof \Hprose\Future;
    }

    function delayed($duration, $value) {
        $future = new \Hprose\Future();
        \Hprose\setTimeout(function() use ($future, $value) {
            \Hprose\trycatch(
                function() use ($future, $value) {
                    if (is_callable($value)) {
                        $value = call_user_func($value);
                    }
                    $future->resolve($value);
                },
                array($future, "reject")
            );
        }, $duration);
        return $future;
    }

    function error($e) {
        $future = new \Hprose\Future();
        $future->reject($e);
        return $future;
    }

    function value($v) {
        $future = new \Hprose\Future();
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
        return \Hprose\trycatch(
            function() use ($computation) {
                $result = call_user_func($computation);
                return value($result);
            },
            function($e) {
                return error($e);
            }
        );
    }

    function promise($executor) {
        $future = new \Hprose\Future();
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
        $array = toFuture($array);
        return $array->then(
            function($array) {
                $keys = array_keys($array);
                $n = count($array);
                $result = array();
                if ($n === 0) {
                    return value($result);
                }
                $future = new \Hprose\Future();
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
                $future = new \Hprose\Future();
                $onfulfilled = array($future, "resolve");
                $onrejected = array($future, "reject");
                foreach ($array as $element) {
                    toFuture($element)->then($onfulfilled, $onrejected);
                };
                return $future;
            }
        );
    }

    function any($array) {
        return toFuture($array)->then(
            function($array) {
                $keys = array_keys($array);
                $n = count($array);
                $result = array();
                if ($n === 0) {
                    throw new \RangeException('any(): $array must not be empty');
                }
                $reasons = array();
                $future = new \Hprose\Future();
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
        $array = toFuture($array);
        return $array->then(
            function($array) {
                $keys = array_keys($array);
                $n = count($array);
                $result = array();
                if ($n === 0) {
                    return value($result);
                }
                $future = new \Hprose\Future();
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
                };
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
        return function() use ($handler) {
            return all(func_get_args())->then(
                function($args) use ($handler) {
                    return call_user_func_array($handler, $args);
                }
            );
        };
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

    function diff($array1, $array2/*, $...*/) {
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

    function udiff($array1, $array2/*, $...*/) {
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
}

namespace Hprose\Promise {
    function all($array) {
        return \Hprose\Future\all($array);
    }
    function race($array) {
        return \Hprose\Future\race($array);
    }
    function resolve($value) {
        return \Hprose\Future\value($value);
    }
    function reject($reason) {
        return \Hprose\Future\error($reason);
    }
}
