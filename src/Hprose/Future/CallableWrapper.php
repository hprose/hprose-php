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
 * Hprose/Future/CallableWrapper.php                      *
 *                                                        *
 * Future CallableWrapper for php 5.3+                    *
 *                                                        *
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Future;

class CallableWrapper extends Wrapper {
    public function __invoke() {
        $obj = $this->obj;
        return all(func_get_args())->then(function($args) use ($obj) {
            return call_user_func_array($obj, $args);
        });
    }
}
