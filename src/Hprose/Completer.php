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
 * LastModified: May 5, 2015                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose {
    class Completer {
        private $results = array();
        private $errors = array();
        private $callbacks = array();
        private $onerror = null;
        private $future;

        public function __construct() {
            $completer = new \stdClass();
            $completer->results = &$this->results;
            $completer->errors = &$this->errors;
            $completer->callbacks = &$this->callbacks;
            $completer->onerror = &$this->onerror;
            $this->future = new Future($completer);
        }
        
        // Calling complete must not be done more than once.
        public function complete($result) {
            $this->results[0] = $result;
            $callbacks = $this->callbacks;
            if (count($callbacks) > 0) {
                $this->callbacks = array();
                foreach ($callbacks as $callback) {
                    try {
                        if ($this->results[0] instanceof Future) {
                            $this->results[0] = $this->results[0]->then($callback);
                        }
                        else {
                            $this->results[0] = $callback($this->results[0]);
                        }
                    }
                    catch (\Exception $e) {
                        $this->completeError($e);
                    }
                }
            }
        }

        public function completeError($error) {
            if ($this->onerror != null) {
                $onerror = $this->onerror;
                $onerror($error);
            }
            else {
                $this->errors[] = $error;
            }
        }

        public function future() {
            return $this->future;
        }
    }
}