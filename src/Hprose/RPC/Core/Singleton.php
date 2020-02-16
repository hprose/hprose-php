<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Core/Singleton.php                            |
|                                                          |
| LastModified: Jun 7, 2019                                |
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