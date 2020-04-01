<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Proxy.php                                                |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

class Proxy {
    private $client;
    private $name;
    private $namespace;
    private $methodCache = [];
    public function __construct(Client $client, string $namespace) {
        $this->client = $client;
        $this->name = $namespace;
        if (empty($namespace)) {
            $this->namespace = '';
        } else {
            $this->namespace = $namespace . '_';
        }
    }
    private function call(string $name, array &$args) {
        $context = null;
        $n = count($args);
        if ($n > 0) {
            if ($args[$n - 1] instanceof ClientContext) {
                $context = array_pop($args);
            }
        }
        return $this->client->invoke($name, $args, $context);
    }
    public function __invoke(...$args) {
        if (empty($this->name)) {
            return;
        }
        return $this->call($this->name, $args);
    }
    public function __call($name, array $args) {
        return $this->call($this->namespace . $name, $args);
    }
    public function __get($name) {
        if (isset($this->methodCache[$name])) {
            return $this->methodCache[$name];
        }
        $method = new Proxy($this->client, $this->namespace . $name);
        $this->methodCache[$name] = $method;
        return $method;
    }
}
