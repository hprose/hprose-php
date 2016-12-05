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
 * Hprose/Promise.php                                     *
 *                                                        *
 * Promise for php 5.3+                                   *
 *                                                        *
 * LastModified: Dec 5, 2016                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

class Promise extends Future {
    public function __construct($executor = null) {
        parent::__construct();
        if (is_callable($executor)) {
            $self = $this;
            call_user_func($executor,
                function($value = NULL) use ($self) {
                    $self->resolve($value);
                },
                function($reason) use ($self) {
                    $self->reject($reason);
                }
            );
        }
    }
}
