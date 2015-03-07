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
 * Hprose/Filter.php                                      *
 *                                                        *
 * hprose filter interface for php 5.3+                   *
 *                                                        *
 * LastModified: Mar 6, 2015                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose {
    interface Filter {
        public function inputFilter($data, $context);
        public function outputFilter($data, $context);
    }
}
