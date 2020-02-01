<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Core/MockServiceContext.php                   |
|                                                          |
| Hprose MockServiceContext for PHP 7.1+                   |
|                                                          |
| LastModified: Jan 31, 2020                               |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

class MockServiceContext extends ServiceContext {
    public $handler;
}