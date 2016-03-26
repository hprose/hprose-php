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
 * Hprose/Future.php                                      *
 *                                                        *
 * hprose future class for php 5.3+                       *
 *                                                        *
 * LastModified: Mar 15, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace {
    if (PHP_MAJOR_VERSION < 7) {
        class TypeError extends Exception {}
    }
}

namespace Hprose {
    class Future {
        const PENDING = 0;
        const FULFILLED = 1;
        const REJECTED = 2;

        public $state = Future::PENDING;
        public $value;
        public $reason;
        private $subscribers = array();

        public function __construct($computation = NULL) {
            if (is_callable($computation)) {
                $self = $this;
                nextTick(function() use ($self, $computation) {
                    trycatch(
                        function() use ($self, $computation) {
                            $self->resolve(call_user_func($computation));
                        },
                        array($self, "reject")
                    );
                });
            }
        }

        private function _call($callback, $next, $x) {
            nextTick(
                function() use ($callback, $next, $x) {
                    trycatch(
                        function() use($callback, $next, $x) {
                            $r = call_user_func($callback, $x);
                            $next->resolve($r);
                        },
                        function($e) use($next) {
                            $next->reject($e);
                        }
                    );
                }
            );
        }

        private function _reject($onreject, $next, $e) {
            if (is_callable($onreject)) {
                $this->_call($onreject, $next, $e);
            }
            else {
                $next->reject($e);
            }
        }

        private function _resolve($onfulfill, $onreject, $next, $x) {
            $self = $this;
            $resolvePromise = function($y) use ($onfulfill, $onreject, $self, $next) {
                $self->_resolve($onfulfill, $onreject, $next, $y);
            };
            $rejectPromise = function($r) use ($onreject, $self, $next) {
                $self->_reject($onreject, $next, $r);
            };
            if (Future\isFuture($x)) {
                if ($x === $this) {
                    $rejectPromise(new \TypeError('Self resolution'));
                    return;
                }
                $x->then($resolvePromise, $rejectPromise);
                return;
            }
            if (($x !== NULL) and is_object($x) or is_string($x)) {
                $then = array($x, "then");
                if (is_callable($then)) {
                    $notrun = true;
                    trycatch(
                        function() use ($then, &$notrun, $resolvePromise, $rejectPromise) {
                            call_user_func($then,
                                function($y) use (&$notrun, $resolvePromise) {
                                    if ($notrun) {
                                        $notrun = false;
                                        $resolvePromise($y);
                                    }
                                },
                                function($r) use (&$notrun, $rejectPromise) {
                                    if ($notrun) {
                                        $notrun = false;
                                        $rejectPromise($r);
                                    }
                                }
                            );
                        },
                        function($e) use (&$notrun, $rejectPromise) {
                            if ($notrun) {
                                $notrun = false;
                                $rejectPromise($e);
                            }
                        }
                    );
                    return;
                }
            }
            if ($onfulfill) {
                $this->_call($onfulfill, $next, $x);
            }
            else {
                $next->resolve($x);
            }
        }

        public function resolve($value) {
            if ($this->state === self::PENDING) {
                $this->state = self::FULFILLED;
                $this->value = $value;
                while (count($this->subscribers) > 0) {
                    $subscriber = array_shift($this->subscribers);
                    $this->_resolve($subscriber['onfulfill'],
                                    $subscriber['onreject'],
                                    $subscriber['next'],
                                    $value);
                }
            }
        }

        public function reject($reason) {
            if ($this->state === self::PENDING) {
                $this->state = self::REJECTED;
                $this->reason = $reason;
                while (count($this->subscribers) > 0) {
                    $subscriber = array_shift($this->subscribers);
                    if (is_callable($subscriber['onreject'])) {
                        $this->_call($subscriber['onreject'],
                                     $subscriber['next'],
                                     $reason);
                    }
                    else {
                        $subscriber['next']->reject($reason);
                    }
                }
            }
        }

        public function then($onfulfill, $onreject = NULL) {
            if (!is_callable($onfulfill)) { $onfulfill = NULL; }
            if (!is_callable($onreject)) { $onreject = NULL; }
            if (($onfulfill !== NULL) or ($onreject !== NULL)) {
                $next = new Future();
                if ($this->state === self::FULFILLED) {
                    if ($onfulfill !== NULL) {
                        $this->_resolve($onfulfill, $onreject, $next, $this->value);
                    }
                    else {
                        $next->resolve($this->value);
                    }
                }
                elseif ($this->state === self::REJECTED) {
                    if ($onreject !== NULL) {
                        $this->_call($onreject, $next, $this->reason);
                    }
                    else {
                        $next->reject($this->reason);
                    }
                }
                else {
                    array_push($this->subscribers, array(
                        'onfulfill' => $onfulfill,
                        'onreject' => $onreject,
                        'next' => $next
                    ));
                }
                return $next;
            }
            return $this;
        }

        public function inspect() {
            switch ($this->state) {
                case self::PENDING: return array('state' => 'pending');
                case self::FULFILLED: return array('state' => 'fulfilled', 'value' => $this->value);
                case self::REJECTED: return array('state' => 'rejected', 'reason' => $this->reason);
            }
        }

        public function catchError($onreject, $test = NULL) {
            if (is_callable($test)) {
                $self = $this;
                return $this->then(NULL,
                    function($e) use ($self, $onreject, $test) {
                        if (call_user_func($test, $e)) {
                            return $this->then(NULL, $onreject);
                        }
                        else {
                            throw $e;
                        }
                    }
                );
            }
            return $this->then(NULL, $onreject);
        }

        public function whenComplete($action) {
            return $this->then(
                function($v) use ($action) {
                    $f = call_user_func($action);
                    if ($f === NULL) { return $v; }
                    $f = Future\toFuture($f);
                    return $f->then(function() use($v) { return $v; });
                },
                function($e) use ($action) {
                    $f = call_user_func($action);
                    if ($f === NULL) { throw $e; }
                    $f = Future\toFuture($f);
                    return $f->then(function() use($e) { throw $e; });
                }
            );
        }

        public function timeout($duration, $reason = NULL) {
            $future = new Future();
            $timeoutId = setTimeout(function() use ($future, $reason) {
                $future->reject($reason || new TimeoutException('timeout'));
            }, $duration);
            $this->whenComplete(function() use ($timeoutId) {
                clearTimeout($timeoutId);
            })
            .then(array($future, 'resolve'), array($future, 'reject'));
            return $future;
        }

        public function delay($duration) {
            $future = new Future();
            $this->then(
                function($result) {
                    setTimeout(
                        function() use ($result) {
                            $future->resolve($result);
                        },
                        $duration
                    );
                },
                array($future, 'reject')
            );
            return $future;
        }

        public function tap($onfulfilledSideEffect) {
            return $this->then(
                function($result) use ($onfulfilledSideEffect) {
                    call_user_func($onfulfilledSideEffect, $result);
                    return $result;
                }
            );
        }

        public function spread($onfulfilledArray) {
            return $this->then(
                function($array) use ($onfulfilledArray) {
                    return call_user_func_array($onfulfilledArray, $array);
                }
            );
        }

        public function get($key) {
            return $this->then(
                function($result) use ($key) {
                    return $result[$key];
                }
            );
        }

        public function __get($key) {
            return $this->get($key);
        }

        public function set($key, $value) {
            return $this->then(
                function($result) use ($key, $value) {
                    $result[$key] = $value;
                    return $result;
                }
            );
        }

        public function __set($key, $value) {
            $this->set($key, $value);
        }

        public function apply($method, $args = NULL) {
            $args = $args || [];
            return $this->then(
                function($result) use ($method, $args) {
                    return Future\all($args)->then(
                        function($args) use ($method) {
                            return call_user_func_array(array($result, $method), $args);
                        }
                    );
                }
            );
        }

        public function call($method/*, arg1, arg2, ...argN*/) {
            return $this->apply($method, array_slice(func_get_args(), 1));
        }

        public function __call($method, $args) {
            return $this->apply($method, $args);
        }

        public function each($callback) {
            return Future\each($this, $callback);
        }

        public function every($callback) {
            return Future\every($this, $callback);
        }

        public function some($callback) {
            return Future\some($this, $callback);
        }

        public function filter($callback, $flag = 0) {
            return Future\filter($this, $callback, $flag);
        }

        public function map($callback) {
            return Future\map($this, $callback);
        }

        public function reduce($callback, $initial = NULL) {
            return Future\reduce($this, $callback, $initial);
        }

        public function search($searchElement, $strict = false) {
            return Future\search($this, $searchElement, $strict);
        }

        public function includes($searchElement, $strict = false) {
            return Future\includes($this, $searchElement, $strict);
        }

    }
}
