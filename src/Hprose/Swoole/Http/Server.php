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
 * Hprose/Swoole/Http/Server.php                          *
 *                                                        *
 * hprose swoole http server library for php 5.3+         *
 *                                                        *
 * LastModified: Apr 8, 2016                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Http {
    class Server extends Service {
        public $server;
        public function __construct($host, $port, $mode = SWOOLE_PROCESS) {
            parent::__construct();
            $this->server = new \swoole_http_server($host, $port, $mode);
        }
        public function set($setting) {
            $this->server->set($setting);
        }
        public function addListener($host, $port) {
            $this->server->addListener($host, $port);
        }
        public function on($name, $callback) {
            $this->server->on($name, $callback);
        }
        public function listen($host, $port, $type) {
            return $this->server->listen($host, $port, $type);
        }
        public function start() {
            $this->server->on('request', array($this, 'handle'));
            $this->server->start();
        }
    }
}
