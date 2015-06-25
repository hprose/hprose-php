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
 * Hprose/Completer.php                                   *
 *                                                        *
 * hprose Completer class for php 5.3+                    *
 *                                                        *
 * LastModified: Jun 25, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose {
    class Completer {
        private $future;

        public function __construct() {
            $this->future = new Future();
        }

        private function resolve($onComplete, $onError, $x) {
            if ($x instanceof Future) {
                if ($x === $this->future) {
                    throw new \Exception('Self resolution');
                }
                return $x->then($onComplete, $onError);
            }
            if ($onComplete !== null) {
                return Future::create($onComplete, $x);
            }
            return $x;
        }

        // Calling complete must not be done more than once.
        public function complete($result) {
            if ($this->future->status === Future::PENDING) {
                $this->future->status = Future::FULFILLED;
                $this->future->result = $result;
                while (count($this->future->callbacks) > 0) {
                    $callback = array_shift($this->future->callbacks);
                    $result = $this->resolve($callback[0], $callback[1], $result);
                }
            }
        }

        public function completeError($error) {
            if ($this->future->status === Future::PENDING) {
                $this->future->status = Future::REJECTED;
                $this->future->error = $error;
                while (count($this->future->callbacks) > 0) {
                    $callback = array_shift($this->future->callbacks);
                    if ($callback[1] !== null) {
                        $result = Future::create($callback[1], $error);
                        break;
                    }
                }
                if (count($this->future->callbacks) > 0) {
                    do {
                        $callback = array_shift($this->future->callbacks);
                        $result = $this->resolve($callback[0], $callback[1], $result);
                    } while (count($this->future->callbacks) > 0);
                }
            }
        }

        public function future() {
            return $this->future;
        }
    }
}
