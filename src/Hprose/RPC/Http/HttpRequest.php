<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| HttpRequest.php                                          |
|                                                          |
| LastModified: Apr 4, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Http;

class HttpRequest {
    public $server;
    public $headers = [];
    public $method;
    public $address;
    public $port;
    public $path;
    public function __construct($server) {
        $this->server = $server;
        $this->headers = $this->getHeaders();
        $this->method = $_SERVER['REQUEST_METHOD'] ?? '';
        $this->address = $_SERVER['REMOTE_ADDR'] ?? '';
        $this->port = $_SERVER['REMOTE_PORT'] ?? 0;
        $this->path = $_SERVER['REQUEST_URI'] ?? '/';
    }
    public function body(): string {
        return file_get_contents("php://input");
    }
    private function getHeaders(): array{
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$name] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }
        if (isset($_SERVER['CONTENT_MD5'])) {
            $headers['Content-Md5'] = $_SERVER['CONTENT_MD5'];
        }
        if (!isset($headers['Authorization'])) {
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
                $basic_pass = $_SERVER['PHP_AUTH_PW'] ?? '';
                $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }
        return $headers;
    }
}