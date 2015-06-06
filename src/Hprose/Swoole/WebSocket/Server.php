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
 * LastModified: Jun 6, 2015                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\WebSocket {
    class Server extends Service {
        private $ws;
        public function __construct($host, $port, $mode = SWOOLE_PROCESS) {
            parent::__construct();
            $this->ws = new \swoole_websocket_server($host, $port, $mode);
        }
        public function set($setting) {
            $this->ws->set($setting);
        }
        public function addListener($host, $port) {
            $this->ws->addListener($host, $port);
        }
        public function start() {
            $this->set_ws_handle($this->ws);
            $this->ws->on('request', array($this, 'handle'));
            $this->ws->start();
        }
    }
}
