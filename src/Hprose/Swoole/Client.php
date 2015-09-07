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
 * LastModified: Apr 19, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole {
    class Client {
        private $real_client = null;
        private function initUrl($url) {
            if ($url) {
                $p = parse_url($url);
                if ($p) {
                    switch (strtolower($p['scheme'])) {
                        case 'tcp':
                        case 'tcp4':
                        case 'tcp6':
                        case 'unix':
                            $this->real_client = new \Hprose\Swoole\Socket\Client($url);
                            break;
                        case 'http':
                        case 'https':
                            $this->real_client = new \Hprose\Http\Client($url);
                            break;
                        default:
                            throw new \Exception("Only support http, https, tcp, tcp4, tcp6 or unix scheme");
                    }
                }
                else {
                    throw new \Exception("Can't parse this url: " . $url);
                }
            }
        }
        public function __construct($url = '') {
            $this->initUrl($url);
        }
        public function useService($url = '', $namespace = '') {
            $this->initUrl($url);
            if ($this->real_client) {
                return $this->real_client->useService('', $namespace);
            }
            return null;
        }
        public function invoke($name, &$args = array(), $byref = false, $mode = \Hprose\ResultMode::Normal, $simple = null, $callback = null) {
            if ($this->real_client) {
                return $this->real_client->invoke($name, $args, $byref, $mode, $simple, $callback);
            }
            return null;
        }
        public function __call($name, $args) {
            return call_user_func_array(array($this->real_client, $name), $args);
        }
        public function __set($name, $value) {
            $this->real_client->$name = $value;
        }
        public function __get($name) {
            return $this->real_client->$name;
        }
        public function __isset($name) {
            return isset($this->real_client->$name);
        }
        public function __unset($name) {
            unset($this->real_client->$name);
        }
    }
}
