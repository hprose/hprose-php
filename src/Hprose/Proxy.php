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
 * LastModified: Jul 27, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

use Closure;
use ArrayAccess;

class Proxy implements ArrayAccess {
    private $client;
    private $namespace;
    private $methodCache = array();
    private $settings = array();
    public function __construct(Client $client, $namespace = '') {
        $this->client = $client;
        $this->namespace = $namespace;
    }
    public function __invoke() {
        $args = func_get_args();
        $name = substr($this->namespace, 0, -1);
        $settings = new InvokeSettings($this->settings);
        $n = count($args);
        if ($n > 0) {
            if ($args[$n - 1] instanceof Closure) {
                $callback = array_pop($args);
                return $this->client->invoke($name, $args, $callback, $settings);
            }
            else if ($args[$n - 1] instanceof InvokeSettings) {
                $settings->settings = array_merge($settings->settings, array_pop($args)->settings);
                if (($n > 1) && ($args[$n - 2] instanceof Closure)) {
                    $callback = array_pop($args);
                    return $this->client->invoke($name, $args, $callback, $settings);
                }
                return $this->client->invoke($name, $args, $settings);
            }
            else if (($n > 1) && is_array($args[$n - 1]) &&
                    ($args[$n - 2] instanceof Closure)) {
                $settings->settings = array_merge($settings->settings, array_pop($args));
                $callback = array_pop($args);
                return $this->client->invoke($name, $args, $callback, $settings);
            }
        }
        return $this->client->invoke($name, $args, $settings);
    }
    public function __call($name, array $args) {
        if (isset($this->methodCache[$name])) {
            $method = $this->methodCache[$name];
            return call_user_func_array($method, $args);
        }
        $name = $this->namespace . $name;
        $n = count($args);
        if ($n > 0) {
            if ($args[$n - 1] instanceof Closure) {
                $callback = array_pop($args);
                return $this->client->invoke($name, $args, $callback);
            }
            else if ($args[$n - 1] instanceof InvokeSettings) {
                if (($n > 1) && ($args[$n - 2] instanceof Closure)) {
                    $settings = array_pop($args);
                    $callback = array_pop($args);
                    return $this->client->invoke($name, $args, $callback, $settings);
                }
                $settings = array_pop($args);
                return $this->client->invoke($name, $args, $settings);
            }
            else if (($n > 1) && is_array($args[$n - 1]) &&
                    ($args[$n - 2] instanceof Closure)) {
                $settings = new InvokeSettings(array_pop($args));
                $callback = array_pop($args);
                return $this->client->invoke($name, $args, $callback, $settings);
            }
        }
        return $this->client->invoke($name, $args);
    }
    public function __get($name) {
        if (isset($this->methodCache[$name])) {
            return $this->methodCache[$name];
        }
        $method = new Proxy($this->client, $this->namespace . $name . '_');
        $this->methodCache[$name] = $method;
        return $method;
    }
    public function offsetSet($name, $value) {
        $this->settings[$name] = $value;
    }
    public function offsetGet($name) {
        return isset($this->settings[$name]) ? $this->settings[$name] : null;
    }
    public function offsetExists($name) {
        return isset($this->settings[$name]);
    }
    public function offsetUnset($name) {
        unset($this->settings[$name]);
    }
}
