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
 * Hprose.php                                             *
 *                                                        *
 * hprose for php 5.3+                                    *
 *                                                        *
 * LastModified: Jul 10, 2017                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

require_once __DIR__ . '/init.php';

// Autoload for non-composer applications
spl_autoload_register(function ($className) {
    if ((strlen($className) > 7) && (strtolower(substr($className, 0, 7)) === "hprose\\")) {
        $file = __DIR__ . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $className) . ".php";
        if (is_file($file)) {
            include $file;
            return true;
        }
    }
    return false;
});
