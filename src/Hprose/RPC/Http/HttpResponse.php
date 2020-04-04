<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| HttpResponse.php                                         |
|                                                          |
| LastModified: Apr 4, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Http;

class HttpResponse {
    public $headers = [];
    public function end(int $code = 200, string $data = ''): void {
        http_response_code($code);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        echo $data;
    }
}