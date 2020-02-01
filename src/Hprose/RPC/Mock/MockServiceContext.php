<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Mock/MockServiceContext.php                   |
|                                                          |
| Hprose MockServiceContext for PHP 7.1+                   |
|                                                          |
| LastModified: Feb 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Mock;

use Hprose\RPC\Core\ServiceContext;

class MockServiceContext extends ServiceContext {
    public $handler;
}