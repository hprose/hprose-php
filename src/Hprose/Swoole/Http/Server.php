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
 * LastModified: Jun 6, 2015                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Http {
    class Server extends Service {
        private $http;
        public function __construct($host, $port, $mode = SWOOLE_PROCESS) {
            parent::__construct();
            $this->http = new \swoole_http_server($host, $port, $mode);
        }
        public function set($setting) {
            $this->http->set($setting);
        }
        public function addListener($host, $port) {
            $this->http->addListener($host, $port);
        }
        public function start() {
            $this->http->on('request', array($this, 'handle'));
            $this->http->start();
        }
    }
}
