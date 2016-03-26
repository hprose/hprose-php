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
 * Hprose/Deferred.php                                    *
 *                                                        *
 * hprose Deferred class for php 5.3+                     *
 *                                                        *
 * LastModified: Mar 26, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose {
    class Deferred {
        public $promise;
        public function __construct() {
            $this->promise = new Future();
        }
        public function resolve($value) {
            $this->promise->resolve($value);
        }
        public function reject($reason) {
            $this->promise->reject($reason);
        }
    }
}
