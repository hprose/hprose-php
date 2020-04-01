<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| MockHandler.php                                          |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Mock;

use Exception;
use Hprose\RPC\Core\Handler;
use Hprose\RPC\Core\Service;
use Hprose\RPC\Core\ServiceContext;

class MockHandler implements Handler {
    public static $serverTypes = ['Hprose\\RPC\\Mock\\MockServer'];
    public $service;
    public function __construct(Service $service) {
        $this->service = $service;
    }
    public function bind($server): void {
        MockAgent::register($server->address, [$this, 'handler']);
    }
    public function handler(string $address, string $request): string {
        if (strlen($request) > $this->service->maxRequestLength) {
            throw new Exception('Request entity too large');
        }
        $context = new ServiceContext($this->service);
        $addressInfo = [
            'family' => 'mock',
            'address' => $address,
            'port' => 0,
        ];
        $context->remoteAddress = $addressInfo;
        $context->localAddress = $addressInfo;
        $context->handler = $this;
        return $this->service->handle($request, $context);
    }
}
