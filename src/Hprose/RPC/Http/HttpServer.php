<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Http/HttpServer.php                           |
|                                                          |
| LastModified: Feb 8, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Http;

use Hprose\RPC\Core\Singleton;

class HttpServer {
    use Singleton;
    private $handler;
    public $address;
    public $port;
    public function __construct() {
        $this->headers = $this->getHeaders();
        $this->address = $_SERVER['SERVER_ADDR'] ?? '';
        $this->port = $_SERVER['SERVER_PORT'] ?? 80;
    }
    public function onRequest(callable $handler) {
        $this->$handler = $handler;
    }
    public function listen() {
        call_user_func($this->handler, new HttpRequest($this), new HttpResponse());
    }
    public function close() {}
}