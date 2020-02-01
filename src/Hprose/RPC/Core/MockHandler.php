<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Core/MockHandler.php                          |
|                                                          |
| Hprose MockHandler for PHP 7.1+                          |
|                                                          |
| LastModified: Feb 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

use Exception;

class MockHandler implements Handler {
    public static $serverTypes = ['Hprose\\RPC\\Core\\MockServer'];
    public $service;
    public function __construct($service) {
        $this->service = $service;
    }
    public function bind($server): void {
        MockAgent::register($server->address, [$this, 'handler']);
    }
    public function handler(string $address, string $request): string {
        if (strlen($request) > $this->service->maxRequestLength) {
            throw new Exception('Request entity too large');
        }
        $context = new MockServiceContext($this->service);
        $addressInfo = ['family' => 'mock', 'address' => $address, 'port' => 0];
        $context->remoteAddress = $addressInfo;
        $context->localAddress = $addressInfo;
        $context->handler = $this;
        return $this->service->handle($request, $context);
    }
}
