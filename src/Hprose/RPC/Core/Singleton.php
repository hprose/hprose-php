<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Singleton.php                                            |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

trait Singleton {
    private static $instance = null;
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
    }
}