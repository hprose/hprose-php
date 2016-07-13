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
                    $this->type = SWOOLE_TCP;
                    $this->host = $p['host'];
                    $this->port = $p['port'];
                    break;
                case 'tcp6':
                    $this->type = SWOOLE_TCP6;
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
                $this->hdtrans = new HalfDuplexSocketTransporter($this);
            }
            $this->hdtrans->sendAndReceive($request, $future, $context);
        }
        if ($context->oneway) {
            $future->resolve(null);
        }
        return $future;
    }
}

/*
class Client extends \Hprose\Client {
    const MAX_PACK_LEN = 0x200000;
    static private $default_setting = array(
        'open_length_check' => true,
        'package_length_type' => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 4,
        'open_eof_check' => false,
    );
    public $setting = array();
    private $sync_client;
    public $pool = array();
    private $type = SWOOLE_TCP;
    private $host = "";
    private $port = 0;
    private $pool_timeout = 100;
    private function send($client, $data) {
        $len = strlen($data);
        if ($len < self::MAX_PACK_LEN - 4) {
            return $client->send(pack("N", $len) . $data);
        }
        if (!$client->send(pack("N", $len))) {
            return false;
        }
        for ($i = 0; $i < $len; $i += self::MAX_PACK_LEN) {
            if (!$client->send(substr($data, $i, min($len - $i, self::MAX_PACK_LEN)))) {
                return false;
            }
        }
        return true;
    }
    private function recv($client) {
        $data = $client->recv();
        if ($data === false) {
            throw new \Exception(socket_strerror($client->errCode));
        }
        return substr($data, 4);
    }
    private function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val{strlen($val)-1});
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return $val;
    }
    protected function setUri($uri) {
        parent::setUri($uri);
        $p = parse_url($uri);
        if ($p) {
            switch (strtolower($p['scheme'])) {
                case 'tcp':
                case 'tcp4':
                    $this->type = SWOOLE_TCP;
                    $this->host = $p['host'];
                    $this->port = $p['port'];
                    break;
                case 'tcp6':
                    $this->type = SWOOLE_TCP6;
                    $this->host = $p['host'];
                    $this->port = $p['port'];
                    break;
                case 'unix':
                    $this->type = SWOOLE_UNIX_STREAM;
                    $this->host = $p['path'];
                    $this->port = 0;
                    break;
                default:
                    throw new \Exception("Only support tcp, tcp4, tcp6 or unix scheme");
            }
            $this->sync_client = new \swoole_client($this->type | SWOOLE_KEEP);
            $this->pool = array();
        }
        else {
            throw new \Exception("Can't parse this url: " . $uri);
        }
    }
    public function set($setting) {
        $this->setting = array_replace($this->setting, $setting);
    }
    protected function sendAndReceive($request, \stdClass $context) {
        $self = $this;
        $result = new \Hprose\Future();
        $noop = function() {};
        $on_connect = function($client) use ($self, $request, $result) {
            if (!$self->send($client, $request)) {
                $result->reject(new \Exception(socket_strerror($client->errCode)));
            }
        };
        $on_error = function($client) use ($result) {
            $result->reject(new \Exception(socket_strerror($client->errCode)));
        };
        $on_receive = function($client, $data) use ($self, $result, $noop) {
            swoole_timer_clear($client->timer);
            $client->on("connect", $noop);
            $client->on("error", $noop);
            $client->on("receive", $noop);
            //$client->timer = swoole_timer_after($self->pool_timeout, function () use ($client) { $client->close(); });
            array_push($self->pool, $client);
            try {
                $result->resolve(substr($data, 4));
            }
            catch(\Exception $e) {
            }
        };
        $client = null;
        while (count($this->pool) > 0) {
            $client = array_pop($this->pool);
            if ($client->isConnected()) break;
        }
        if ($client == null || !$client->isConnected()) {
            $client = new \swoole_client($this->type, SWOOLE_SOCK_ASYNC);
            $setting = array_replace($this->setting, self::$default_setting);
            if (!isset($setting['package_max_length'])) {
                $setting['package_max_length'] = $this->return_bytes(ini_get('memory_limit'));
            }
            if ($setting['package_max_length'] < 0) {
                $setting['package_max_length'] = 0x7fffffff;
            }
            $client->set($setting);
            $client->on("connect", $on_connect);
            $client->on("error", $on_error);
            $client->on("receive", $on_receive);
            $client->on("close", $noop);
            $client->connect($this->host, $this->port);
        }
        else {
            swoole_timer_clear($client->timer);
            $client->on("error", $on_error);
            $client->on("receive", $on_receive);
            if (!$this->send($client, $request)) {
                $result->reject(new \Exception(socket_strerror($client->errCode)));
            }
        }
        $client->timer = swoole_timer_after($this->timeout, function () use ($client) { $client->close(); });
        return $result;
    }
    public function setPoolTimeout($value) {
        $this->pool_timeout = $value;
    }
    public function getPoolTimeout() {
        return $this->pool_timeout;
    }
}

*/