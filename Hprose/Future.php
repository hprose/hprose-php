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
 * LastModified: May 4, 2015                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose {
    class Future {
        private $completer;
        // This __construct only public for Completer.
        public function __construct($completer) {
            $this->completer = $completer;
        }
        public function then($callback) {
            if (count($this->completer->results) > 0) {
                try {
                    if ($this->completer->results[0] instanceof Future) {
                        $this->completer->results[0] = $this->completer->results[0]->then($callback);
                    }
                    else {
                        $this->completer->results[0] = $callback($this->completer->results[0]);
                    }
                }
                catch (Exception $e) {
                    $this->completer->completeError($e);
                }
            }
            else {
                $this->completer->callbacks[] = $callback;
            }
            return $this;
        }
        public function catchError($onerror) {
            $this->completer->onerror = $onerror;
            $errors = $this->completer->errors;
            if (count($errors) > 0) {
                $this->completer->errors = array();
                foreach ($errors as $error) {
                    $onerror($error);
                }
            }
            return $this;
        }
    }
}