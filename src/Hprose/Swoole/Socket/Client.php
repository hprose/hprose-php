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
 * Hprose/Swoole/Socket/Client.php                        *
 *                                                        *
 * hprose swoole socket client library for php 5.3+       *
 *                                                        *
 * LastModified: Jul 14, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Socket;

use stdClass;
use Exception;
use Hprose\Future;

class Client extends \Hprose\Client {
    public $type;
    public $host = "";
    public $port = 0;
    public $fullDuplex = false;
    public $maxPoolSize = 10;
    public $poolTimeout = 30000;
    public $settings = array();
    private $fdtrans;
    private $hdtrans;
    public function __construct($uris = null) {
        parent::__construct($uris);
        swoole_async_set(array(
            "socket_buffer_size" => 2 * 1024 * 1024 * 1024,
            "socket_dontwait" => false
        ));
    }
    public function getHost() {
        return $this->host;
    }
    public function getPort() {
        return $this->port;
    }
    public function getType() {
        return $this->type;
    }
    public function setFullDuplex($value) {
        $this->fullDuplex = $value;
    }
    public function isFullDuplex() {
        return $this->fullDuplex;
    }
    public function setMaxPoolSize($value) {
        $this->maxPoolSize = $value;
    }
    public function getMaxPoolSize() {
        return $this->maxPoolSize;
    }
    public function setPoolTimeout($value) {
        $this->poolTimeout = $value;
    }
    public function getPoolTimeout() {
        return $this->poolTimeout;
    }
    public function set($key, $value) {
        $this->settings[$key] = $value;
        return $this;
    }
    protected function setUri($uri) {
        parent::setUri($uri);
        $p = parse_url($uri);
        if ($p) {
            switch (strtolower($p['scheme'])) {
                case 'tcp':
                case 'tcp4':
                case 'ssl':
                case 'sslv2':
                case 'sslv3':
                case 'tls':
                    $this->type = SWOOLE_SOCK_TCP;
                    $this->host = $p['host'];
                    $this->port = $p['port'];
                    break;
                case 'tcp6':
                    $this->type = SWOOLE_SOCK_TCP6;
                    $this->host = $p['host'];
                    $this->port = $p['port'];
                    break;
                case 'unix':
                    $this->type = SWOOLE_UNIX_STREAM;
                    $this->host = $p['path'];
                    $this->port = 0;
                    break;
                default:
                    throw new Exception("Only support tcp, tcp4, tcp6 or unix scheme");
            }
            $this->close();
        }
        else {
            throw new Exception("Can't parse this uri: " . $uri);
        }
    }
    public function close() {
        if (isset($this->fdtrans)) {
            $this->fdtrans->close();
        }
        if (isset($this->hdtrans)) {
            $this->hdtrans->close();
        }
    }
    protected function sendAndReceive($request, stdClass $context) {
        $future = new Future();
        if ($this->fullDuplex) {
            if (($this->fdtrans === null) || ($this->fdtrans->uri !== $this->uri)) {
                $this->fdtrans = new FullDuplexTransporter($this);
            }
            $this->fdtrans->sendAndReceive($request, $future, $context);
        }
        else {
            if (($this->hdtrans === null) || ($this->hdtrans->uri !== $this->uri)) {
                $this->hdtrans = new HalfDuplexTransporter($this);
            }
            $this->hdtrans->sendAndReceive($request, $future, $context);
        }
        if ($context->oneway) {
            $future->resolve(null);
        }
        return $future;
    }
}
