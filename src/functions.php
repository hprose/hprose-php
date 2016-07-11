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
 * functions.php                                          *
 *                                                        *
 * hprose functions for php 5.3+                          *
 *                                                        *
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (!function_exists('hprose_serialize')) {
    function hprose_serialize($var, $simple = false) {
        return \Hprose\Formatter::serialize($var, $simple);
    }
}
if (!function_exists('hprose_unserialize')) {
    function hprose_unserialize($data, $simple = false) {
        return \Hprose\Formatter::unserialize($data, $simple);
    }
}
