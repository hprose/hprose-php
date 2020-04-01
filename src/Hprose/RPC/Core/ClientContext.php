<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| ClientContext.php                                        |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

class ClientContext extends Context {
    public function __construct(?array $items = null) {
        if ($items === null) {
            return;
        }
        $this->items = $items;
    }
    public function init(Client $client) {
        $this->client = $client;
        $uris = $client->getUris();
        if (count($uris) > 0) {
            $this->uri = $uris[0];
        }
        if (!isset($this->timeout)) {
            $this->timeout = $client->timeout;
        }
        $this->requestHeaders = array_merge($this->requestHeaders, $client->requestHeaders);
    }
}