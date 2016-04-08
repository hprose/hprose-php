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
 * Hprose/Swoole/WebSocket/Server.php                     *
 *                                                        *
 * hprose swoole websocket server library for php 5.3+    *
 *                                                        *
 * LastModified: Apr 8, 2016                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\WebSocket {
    class Server extends Service {
        public $server;
        public function __construct($host, $port, $mode = SWOOLE_PROCESS) {
            parent::__construct();
            $this->server = new \swoole_websocket_server($host, $port, $mode);
        }
        public function set($setting) {
            $this->server->set($setting);
        }
        public function addListener($host, $port) {
            $this->server->addListener($host, $port);
        }
        public function start() {
            $this->set_ws_handle($this->server);
            $this->server->on('request', array($this, 'handle'));
            $this->server->start();
        }
    }
}
