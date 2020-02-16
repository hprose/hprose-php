<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Http/HttpRequest.php                          |
|                                                          |
| LastModified: Feb 2, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Http;

class HttpRequest {
    public $headers = [];
    public function end(int $code = 200, string $data = ''): void {
        http_response_code($code);
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }
        echo $data;
    }
}