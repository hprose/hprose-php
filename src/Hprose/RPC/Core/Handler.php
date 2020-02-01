<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Core/Handler.php                              |
|                                                          |
| Hprose Service Handler for PHP 7.1+                      |
|                                                          |
| LastModified: Jan 24, 2020                               |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

interface Handler {
    function bind($server): void;
}
