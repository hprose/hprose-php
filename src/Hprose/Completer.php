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
 * LastModified: Jul 11, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

class Completer {
    private $future;

    public function __construct() {
        $this->future = new Future();
    }

    public function future() {
        return $this->future;
    }

    public function complete($result) {
        $this->future->resolve($result);
    }

    public function completeError($error) {
        $this->future->reject($error);
    }

    public function isCompleted() {
        return $this->future->state !== Future::PENDING;
    }

}
