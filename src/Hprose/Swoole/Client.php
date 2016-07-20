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
 * Hprose/Swoole/Client.php                               *
 *                                                        *
 * hprose swoole client library for php 5.3+              *
 *                                                        *
 * LastModified: Jul 20, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole;

class Client {
    private static $clientFactories = array();
    private static $clientFactoriesInited = false;
    public static function registerClientFactory($scheme, $clientFactory) {
        self::$clientFactories[$scheme] = $clientFactory;
    }
    public static function tryRegisterClientFactory($scheme, $clientFactory) {
        if (empty(self::$clientFactories[$scheme])) {
            self::$clientFactories[$scheme] = $clientFactory;
        }
    }
    private static function initClientFactories() {
        self::tryRegisterClientFactory("http", "\\Hprose\\Swoole\\Http\\Client");
        self::tryRegisterClientFactory("https", "\\Hprose\\Swoole\\Http\\Client");
        self::tryRegisterClientFactory("tcp", "\\Hprose\\Swoole\\Socket\\Client");
        self::tryRegisterClientFactory("tcp4", "\\Hprose\\Swoole\\Socket\\Client");
        self::tryRegisterClientFactory("tcp6", "\\Hprose\\Swoole\\Socket\\Client");
        self::tryRegisterClientFactory("ssl", "\\Hprose\\Swoole\\Socket\\Client");
        self::tryRegisterClientFactory("sslv2", "\\Hprose\\Swoole\\Socket\\Client");
        self::tryRegisterClientFactory("sslv3", "\\Hprose\\Swoole\\Socket\\Client");
        self::tryRegisterClientFactory("tls", "\\Hprose\\Swoole\\Socket\\Client");
        self::tryRegisterClientFactory("unix", "\\Hprose\\Swoole\\Socket\\Client");
        self::tryRegisterClientFactory("ws", "\\Hprose\\Swoole\\WebSocket\\Client");
        self::tryRegisterClientFactory("wss", "\\Hprose\\Swoole\\WebSocket\\Client");
        self::$clientFactoriesInited = true;
    }
    private $realClient = null;
    public function __construct($uris) {
        if (!self::$clientFactoriesInited) self::initClientFactories();
        if (is_string($uris)) $uris = array($uris); 
        $scheme = strtolower(parse_url($uris[0], PHP_URL_SCHEME));
        $n = count($uris);
        for ($i = 1; $i < $n; ++$i) {
            if (strtolower(parse_url($uris[$i], PHP_URL_SCHEME)) != $scheme) {
                throw new Exception("Not support multiple protocol.");
            }
        }
        $clientFactory = self::$clientFactories[$scheme];
        if (empty($clientFactory)) {
            throw new Exception("This client doesn't support $scheme scheme.");
        }
        $this->realClient = new $clientFactory($uris);
    }
    public function __call($name, $args) {
        return call_user_func_array(array($this->realClient, $name), $args);
    }
    public function __set($name, $value) {
        $this->realClient->$name = $value;
    }
    public function __get($name) {
        return $this->realClient->$name;
    }
    public function __isset($name) {
        return isset($this->realClient->$name);
    }
    public function __unset($name) {
        unset($this->realClient->$name);
    }
}
