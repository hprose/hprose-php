<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Core/MockServer.php                           |
|                                                          |
| Hprose MockServer for PHP 7.1+                           |
|                                                          |
| LastModified: Jan 31, 2020                               |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

class MockServer {
    public $address;
    public function __construct(string $address) {
        $this->address = $address;
    }
    public function close() {
        MockAgent::cancel($this->address);
    }
}