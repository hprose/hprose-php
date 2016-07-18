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
 * LastModified: Jul 18, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Socket;

use stdClass;
use swoole_server;

class Server extends Service {
    public $server;
    private function parseUrl($uri) {
        $result = new stdClass();
        $p = parse_url($uri);
        if ($p) {
            switch (strtolower($p['scheme'])) {
                case 'tcp':
                case 'tcp4':
                case 'ssl':
                case 'sslv2':
                case 'sslv3':
                case 'tls':
                    $result->type = SWOOLE_SOCK_TCP;
                    $result->host = $p['host'];
                    $result->port = $p['port'];
                    break;
                case 'tcp6':
                    $result->type = SWOOLE_SOCK_TCP6;
                    $result->host = $p['host'];
                    $result->port = $p['port'];
                    break;
                case 'unix':
                    $result->type = SWOOLE_UNIX_STREAM;
                    $result->host = $p['path'];
                    $result->port = 0;
                    break;
                default:
                    throw new Exception("Can't support this scheme: {$p['scheme']}");
            }
        }
        else {
            throw new Exception("Can't parse this uri: " . $uri);
        }
        return $result;
    }
    public function __construct($uri, $mode = SWOOLE_PROCESS) {
        parent::__construct();
        $url = $this->parseUrl($uri);
        $this->server = new swoole_server($url->host, $url->port, $mode, $url->type);
    }
    public function on($name, $callback) {
        $this->server->on($name, $callback);
    }
    public function addListener($host, $port, $type = SWOOLE_SOCK_TCP) {
        $this->server->addListener($host, $port, $type);
    }
    public function listen($uri) {
        $url = $this->parseUrl($uri);
        return $this->server->listen($url->host, $url->port, $url->type);
    }
    public function start() {
        if (is_array($this->settings) && !empty($this->settings)) {
            $this->server->set($this->settings);
        }
        $this->socketHandle($this->server);
        $this->server->start();
    }
}
