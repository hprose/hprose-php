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
 * LastModified: Jun 28, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose {

    class Future {

        const PENDING = 0;
        const FULFILLED = 1;
        const REJECTED = 2;

        public $status = Future::PENDING;
        public $result;
        public $error;
        public $callbacks = array();

        // This __construct only public for Completer.
        public function __construct() {}

        public static function create($callback, $x = null) {
            $completer = new Completer();
            try {
                $completer->complete(call_user_func($callback, $x));
            }
            catch(\Exception $e) {
                $completer->completeError($e);
            }
            return $completer->future();
        }

        public function then($onComplete, $onError = null) {
            if (!is_callable($onComplete)) $onComplete = null;
            if (!is_callable($onError)) $onError = null;
            if (($onComplete !== null) || ($onError !== null)) {
                if (($onComplete !== null) && ($this->status === Future::FULFILLED)) {
                    $x = $this->result;
                    if ($x instanceof Future) {
                        if ($x === $this) {
                            throw new \Exception('Self resolution');
                        }
                        return $x->then($onComplete, $onError);
                    }
                    return Future::create($onComplete, $x);
                }
                if (($onError !== null) && ($this->status === Future::REJECTED)) {
                    return Future::create($onError, $this->error);
                }
                $this->callbacks[] = array($onComplete, $onError);
            }
            return $this;
        }

        public function catchError($onError) {
            return $this->then(null, $onError);
        }

    }
}
