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
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

class Promise extends Future {
    public function __construct($executor) {
        parent::__construct();
        call_user_func($executor, array($this, "resolve"), array($this, "reject"));
    }
}
