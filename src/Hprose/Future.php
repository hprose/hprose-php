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
 * LastModified: Jul 8, 2016                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace {
    if (!class_exists('TypeError')) {
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
                try {
                    $self->resolve(call_user_func($computation));
                }
                catch (Future\UncatchableException $e) {
                    throw $e->getPrevious();
                }
                catch (\Exception $e) {
                    $self->reject($e);
                }
                catch (\Throwable $e) {
                    $self->reject($e);
                }
            }
        }

        /*
            This method is a private method.
            But PHP 5.3 can't call private method in closure,
            so we comment the private keyword.
        */
        /*private*/ function _call($callback, $next, $x) {
            try {
                $r = call_user_func($callback, $x);
                $next->resolve($r);
            }
            catch (Future\UncatchableException $e) {
                throw $e->getPrevious();
            }
            catch (\Exception $e) {
                $next->reject($e);
            }
            catch (\Throwable $e) {
                $next->reject($e);
            }
        }

        /*
            This method is a private method.
            But PHP 5.3 can't call private method in closure,
            so we comment the private keyword.
        */
        /*private*/ function _reject($onreject, $next, $e) {
            if (is_callable($onreject)) {
                $this->_call($onreject, $next, $e);
            }
            else {
                $next->reject($e);
            }
        }

        /*
            This method is a private method.
            But PHP 5.3 can't call private method in closure,
            so we comment the private keyword.
        */
        /*private*/ function _resolve($onfulfill, $onreject, $next, $x) {
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
                    try {
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
                    }
                    catch (Future\UncatchableException $e) {
                        throw $e->getPrevious();
                    }
                    catch (\Exception $e) {
                        if ($notrun) {
                            $notrun = false;
                            $rejectPromise($e);
                        }
                    }
                    catch (\Throwable $e) {
                        if ($notrun) {
                            $notrun = false;
                            $rejectPromise($e);
                        }
                    }
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
                    $this->_resolve($onfulfill, $onreject, $next, $this->value);
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

        public function done($onfulfill, $onreject = NULL) {
            $this->then($onfulfill, $onreject)->then(NULL, function($error) {
                throw new Future\UncatchableException("", 0, $error);
            });
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
                            return $self->then(NULL, $onreject);
                        }
                        else {
                            throw $e;
                        }
                    }
                );
            }
            return $this->then(NULL, $onreject);
        }

        public function fail($onreject) {
            $this->done(NULL, $onreject);
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

        public function complete($oncomplete) {
            return $this->then($oncomplete, $oncomplete);
        }

        public function always($oncomplete) {
            $this->done($oncomplete, $oncomplete);
        }

        public function fill($future) {
            $this->then(array($future, 'resolve'), array($future, 'reject'));
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

        public function __get($key) {
            return $this->then(
                function($result) use ($key) {
                    return $result->$key;
                }
            );
        }

        public function __call($method, $args) {
            if ($args === NULL) {
                $args = array();
            }
            return $this->then(
                function($result) use ($method, $args) {
                    return Future\all($args)->then(
                        function($args) use ($result, $method) {
                            return call_user_func_array(array($result, $method), $args);
                        }
                    );
                }
            );
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

        public function filter($callback, $preserveKeys = false) {
            return Future\filter($this, $callback, $preserveKeys);
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
