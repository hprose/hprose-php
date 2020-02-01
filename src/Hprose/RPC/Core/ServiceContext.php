<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Core/ServiceContext.php                       |
|                                                          |
| Hprose ServiceContext for PHP 7.1+                       |
|                                                          |
| LastModified: Jan 24, 2020                               |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

class ServiceContext extends Context {
    public $service;
    public function __construct(Service $service) {
        $this->service = $service;
    }
}