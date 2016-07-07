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
 * LastModified: Jul 8, 2016                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace {
    if (!interface_exists("Throwable")) {
        interface Throwable {
            public function getMessage();
            public function getCode();
            public function getFile();
            public function getLine();
            public function getTrace();
            public function getTraceAsString();
            public function getPrevious();
            public function __toString();
        }
    }
}

namespace Hprose {
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

    class Wrapper {
        protected $obj;
        public function __construct($obj) {
            $this->obj = $obj;
        }
        public function __call($name, array $arguments) {
            $method = array($this->obj, $name);
            return all($arguments)->then(function($args) use ($method) {
                return call_user_func_array($method, $args);
            });
        }
        public function __get($name) {
            return $this->obj->$name;
        }
        public function __set($name, $value) {
            $this->obj->$name = $value;
        }
        public function __isset($name) {
            return isset($this->obj->$name);
        }
        public function __unset($name) {
            unset($this->obj->$name);
        }
    }

    class CallableWrapper extends Wrapper {
        public function __invoke() {
            $obj = $this->obj;
            return all(func_get_args())->then(function($args) use ($obj) {
                return call_user_func_array($obj, $args);
            });
        }
    }

    function isFuture($obj) {
        return $obj instanceof \Hprose\Future;
    }

    function delayed($duration, $value) {
        $future = new \Hprose\Future();
        \Hprose\setTimeout(function() use ($future, $value) {
            try {
                if (is_callable($value)) {
                    $value = call_user_func($value);
                }
                $future->resolve($value);
            }
            catch (\Exception $e) {
                $future->reject($e);
            }
            catch (\Throwable $e) {
                $future->reject($e);
            }
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
        try {
            return toFuture(call_user_func($computation));
        }
        catch (\Exception $e) {
            return error($e);
        }
        catch (\Throwable $e) {
            return error($e);
        }
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
                foreach ($array as $element) {
                    toFuture($element)->fill($future);
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
        function co($generator) {
            if (is_callable($generator)) {
                $generator = $generator();
            }
            elseif (!($generator instanceof \Generator)) {
                return $generator;
            }
            $future = new \Hprose\Future();
            $next = function() use ($generator, &$next, $future) {
                if ($generator->valid()) {
                    $current = $generator->current();
                    if (is_callable($current)) {
                        $current = $current();
                    }
                    toPromise($current)->then(function($value) use ($generator, &$next) {
                        $generator->send($value);
                        $next();
                    },
                    function($e) use ($generator, &$next) {
                        $generator->throw($e);
                        $next();
                    });
                }
                else {
                    if (method_exists($generator, "getReturn")) {
                        $future->resolve($generator->getReturn());
                    }
                    else {
                        $future->resolve(null);
                    }
                }
            };
            $next();
            return $future;
        }
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
