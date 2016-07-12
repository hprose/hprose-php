<?php
/**********************************************************\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: http://www.hprose.com/                 |
|                   http://www.hprose.org/                 |
|                                                          |
\**********************************************************/

/**********************************************************\
 *                                                        *
 * Hprose/Proxy.php                                       *
 *                                                        *
 * hprose Proxy class for php 5.3+                        *
 *                                                        *
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

class Proxy {
    private $client;
    private $namespace;
    public function __construct(Client $client, $namespace = '') {
        $this->client = $client;
        $this->namespace = $namespace;
    }
    public function __call($name, array $args) {
        $name = $this->namespace . $name;
        $n = count($args);
        if ($n > 0) {
            if (is_callable($args[$n - 1])) {
                $callback = array_pop($args);
                return $this->client->invoke($name, $args, $callback);
            }
            else if ($args[$n - 1] instanceof InvokeSettings) {
                if (($n > 1) && is_callable($args[$n - 2])) {
                    $settings = array_pop($args); 
                    $callback = array_pop($args);
                    return $this->client->invoke($name, $args, $callback, $settings);
                }
                $settings = array_pop($args);
                return $this->client->invoke($name, $args, $settings);
            }
        }
        return $this->client->invoke($name, $args);
    }
    public function __get($name) {
        return new Proxy($this->client, $this->namespace . $name . '_');
    }
}
