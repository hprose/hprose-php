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
 * Hprose/Socket/Server.php                               *
 *                                                        *
 * hprose socket server library for php 5.3+              *
 *                                                        *
 * LastModified: Jul 30, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Socket;

use Exception;

class Server extends Service {
    public $settings = array();
    public $server;
    public $uri;
    public function __construct($uri) {
        parent::__construct();
        $this->uri = $uri;
    }
    public function set($settings) {
        $this->settings = array_replace($this->settings, $settings);
    }
    private function createSocketServer() {
        $uri = $this->uri;
        $scheme = parse_url($uri, PHP_URL_SCHEME);
        if ($scheme == 'unix') {
            $uri = 'unix://' . parse_url($uri, PHP_URL_PATH);
        }
        $errno = 0;
        $errstr = '';
        $context = @stream_context_create($this->settings);
        $server = @stream_socket_server($uri, $errno, $errstr,
                STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
        if ($server === false) {
            throw new Exception($errstr, $errno);
        }
        return $server;
    }
    public function start() {
        $this->server = $this->createSocketServer();
        $this->handle($this->server);
    }
}