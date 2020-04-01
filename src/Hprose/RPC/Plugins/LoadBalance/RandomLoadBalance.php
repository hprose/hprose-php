<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| RandomLoadBalance.php                                    |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Plugins\LoadBalance;

use Hprose\RPC\Core\Context;

class RandomLoadBalance {
    public function handler(string $request, Context $context, callable $next): string {
        $uris = $context->client->getUris();
        $n = count($uris);
        $context->uri = $uris[random_int(0, $n - 1)];
        return $next($request, $context);
    }
}