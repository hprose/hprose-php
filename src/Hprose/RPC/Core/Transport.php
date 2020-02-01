<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Core/Transport.php                            |
|                                                          |
| Hprose ClientCodec for PHP 7.1+                          |
|                                                          |
| LastModified: Jun 7, 2019                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

interface Transport {
    function transport(string $request, Context $context): string;
    function abort(): void;
}