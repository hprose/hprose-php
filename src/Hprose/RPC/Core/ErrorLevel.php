<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| ErrorLevel.php                                           |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

trait ErrorLevel {
    private $errorLevel = [
        E_ERROR => "Fatal Error",
        E_PARSE => "Fatal Error",
        E_CORE_ERROR => "Fatal Error",
        E_COMPILE_ERROR => "Fatal Error",
        E_USER_ERROR => "Fatal Error",
        E_WARNING => "Warning",
        E_CORE_WARNING => "Warning",
        E_USER_WARNING => "Warning",
        E_COMPILE_WARNING => "Warning",
        E_RECOVERABLE_ERROR => "Warning",
        E_NOTICE => "Notice",
        E_USER_NOTICE => "Notice",
        E_STRICT => "Strict",
        E_DEPRECATED => "Deprecated",
        E_USER_DEPRECATED => "Deprecated",
    ];
}