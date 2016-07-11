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
 * Hprose/Promise/functions.php                           *
 *                                                        *
 * some helper functions for php 5.3+                     *
 *                                                        *
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Promise {
    function all($array) {
        return \Hprose\Future\all($array);
    }
    function race($array) {
        return \Hprose\Future\race($array);
    }
    function resolve($value) {
        return \Hprose\Future\value($value);
    }
    function reject($reason) {
        return \Hprose\Future\error($reason);
    }
}
