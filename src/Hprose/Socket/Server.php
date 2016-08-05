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
 * LastModified: Aug 6, 2016                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Socket;

use Exception;

class Server extends Service {
    public $settings = array();
    public $noDelay = true;
    public $keepAlive = true;
    public $uris = array();
    public function __construct($uri) {
        parent::__construct();
        $this->uris[] = $uri;
    }
    public function setNoDelay($value) {
        $this->noDelay = $value;
    }
    public function isNoDelay() {
        return $this->noDelay;
    }
    public function setKeepAlive($value) {
        $this->keepAlive = $value;
    }
    public function isKeepAlive() {
        return $this->keepAlive;
    }
    public function set($settings) {
        $this->settings = array_replace($this->settings, $settings);
    }
    public function addListener($uri) {
        $this->uris[] = $uri;
    }
    private function createSocketServer($uri) {
        $scheme = parse_url($uri, PHP_URL_SCHEME);
        if ($scheme == 'unix') {
            $uri = 'unix://' . parse_url($uri, PHP_URL_PATH);
        }
        $errno = 0;
        $errstr = '';
        $context = @stream_context_create($this->settings);
        $server = @stream_socket_server($uri, $errno, $errstr,
                STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
        if (function_exists('socket_import_stream')) {
            if (($scheme === 'tcp') || ($scheme === 'unix')) {
                $socket = socket_import_stream($server);
                socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, (int)$this->keepAlive);
                if ($scheme === 'tcp') {
                    socket_set_option($socket, SOL_TCP, TCP_NODELAY, (int)$this->noDelay);
                }
            }
        }
        if ($server === false) {
            throw new Exception($errstr, $errno);
        }
        return $server;
    }
    public function start() {
        $servers = array();
        foreach ($this->uris as $uri) {
            $servers[] = $this->createSocketServer($uri);
        }
        $this->handle($servers);
    }
}