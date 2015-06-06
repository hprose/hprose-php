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
 * Hprose/Swoole/Socket/Server.php                        *
 *                                                        *
 * hprose swoole socket server library for php 5.3+       *
 *                                                        *
 * LastModified: Jun 7, 2015                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Socket {
    class Server extends Service {
        private $server;
        private function parseUrl($url) {
            $result = new \stdClass();
            $p = parse_url($url);
            if ($p) {
                switch (strtolower($p['scheme'])) {
                    case 'tcp':
                    case 'tcp4':
                        $result->type = SWOOLE_TCP;
                        $result->host = $p['host'];
                        $result->port = $p['port'];
                        break;
                    case 'tcp6':
                        $result->type = SWOOLE_TCP6;
                        $result->host = $p['host'];
                        $result->port = $p['port'];
                        break;
                    case 'unix':
                        $result->type = SWOOLE_UNIX_STREAM;
                        $result->host = $p['path'];
                        $result->port = 1;
                        break;
                    default:
                        throw new \Exception("Only support tcp, tcp4, tcp6 or unix scheme");
                }
            }
            else {
                throw new \Exception("Can't parse this url: " . $url);
            }
            return $result;
        }
        public function __construct($url, $mode = SWOOLE_PROCESS) {
            parent::__construct();
            $url = $this->parseUrl($url);
            $this->server = new \swoole_server($url->host, $url->port, $mode, $url->type);
        }
        public function addListener($url) {
            $url = $this->parseUrl($url);
            $this->server->addListener($url->host, $url->port, $url->type);
        }
        public function start() {
            $this->handle($this->server);
            $this->server->start();
        }
    }
}
