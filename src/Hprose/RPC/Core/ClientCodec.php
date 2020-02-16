<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Core/ClientCodec.php                          |
|                                                          |
| LastModified: Jun 7, 2019                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

interface ClientCodec {
    function encode(string $name, array &$args, ClientContext $context): string;
    function decode(string $response, ClientContext $context);
}