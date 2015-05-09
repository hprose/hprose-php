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
 * LastModified: Apr 19, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Socket {
    class Client extends \Hprose\Client {
        const MAX_PACK_LEN = 0x200000;
        private function send($client, $data) {
            $len = strlen($data);
            if ($len < self::MAX_PACK_LEN - 4) {
                return $client->send(pack("N", $len) . $data);
            }
            if (!$client->send(pack("N", $len))) {
                return false;
            }
            for ($i = 0; $i < $len; ++$i) {
                if (!$client->send(substr($data, $i, min($len - $i, self::MAX_PACK_LEN)))) {
                    return false;
                }
                $i += self::MAX_PACK_LEN;
            }
            return true;
        }
        private function recv($client) {
            $len = $client->recv(4, 1);
            if ($len === "") {
                throw new \Exception("connection closed");
            }
            if ($len === false) {
                throw new \Exception(socket_strerror($client->errCode));
            }
            $len = unpack("N", $len);
            return $client->recv($len[1], 1);
        }
        function return_bytes($val) {
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
        private $conn_stats = array();
        private $type = SWOOLE_TCP;
        private $host = "";
        private $port = 0;
        private $timeout = 30000;
        private function initUrl($url) {
            if ($url) {
                $p = parse_url($url);
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
                }
                else {
                    throw new \Exception("Can't parse this url: " . $url);
                }
            }
        }
        public function __construct($url = '') {
            parent::__construct($url);
            $this->initUrl($url);
        }
        public function useService($url = '', $namespace = '') {
            $this->initUrl($url);
            return parent::useService($url, $namespace);
        }
        protected function sendAndReceive($request) {
            $client = new \swoole_client($this->type | SWOOLE_KEEP);
            if (!$client->connect($this->host, $this->port, $this->timeout / 1000)) {
                throw new \Exception("connect failed");
            }
            if ($this->send($client, $request)) {
                $response = $this->recv($client);
                if ($response === "") {
                    throw new \Exception("connection closed");
                }
                if ($response === false) {
                    throw new \Exception(socket_strerror($client->errCode));
                }
            }
            else {
                throw new \Exception(socket_strerror($client->errCode));
            }
            $client->close();
            return $response;
        }
        protected function asyncSendAndReceive($request, $use) {
            $self = $this;
            $client = new \swoole_client($this->type, SWOOLE_SOCK_ASYNC);
            $buffer = "";
            $len = "";
            $client->on("connect", function($cli) use ($self, $request, $use) {
                if (!$self->send($cli, $request)) {
                    $self->sendAndReceiveCallback('', new \Exception(socket_strerror($cli->errCode)), $use);
                }
            });
            $client->on("error", function($cli) use ($self, $use) {
                $self->sendAndReceiveCallback('', new \Exception(socket_strerror($cli->errCode)), $use);
            });
            $client->on("receive", function($cli, $data) use ($self, &$buffer, &$len, $use) {
                do {
                    if (count($buffer) == 0 || is_string($len)) {
                        $left = 4 - strlen($len);
                        if (strlen($data) < $left) {
                            $len .= $data;
                            return;
                        }
                        $len .= substr($data, 0, $left);
                        $len = unpack("N", $len);
                        $len = $len[1];
                        $n = strlen($data) - $left;
                    }
                    else {
                        $left = 0;
                        $n = strlen($data);
                    }
                    if ($n == 0) {
                        $buffer = "";
                        return;
                    }
                    if ($len == $n) {
                        $response = $buffer . substr($data, $left);
                        $buffer = "";
                        $len = "";
                        try {
                            $self->sendAndReceiveCallback($response, null, $use);
                        }
                        catch(\Exception $e) {
                        }
                        swoole_timer_clear($cli->timer);
                        $cli->close();
                        return;
                    }
                    if ($len > $n) {
                        $buffer .= substr($data, $left);
                        $len -= $n;
                        return;
                    }
                    $response = $buffer . substr($data, $left, $len);
                    $buffer = "";
                    $data = substr($data, $left + $len);
                    $len = "";
                    try {
                        $self->sendAndReceiveCallback($response, null, $use);
                    }
                    catch(\Exception $e) {
                    }
                } while(true);
            });
            $client->on("close", function($cli) {});
            $client->connect($this->host, $this->port);
            $client->timer = swoole_timer_after($this->timeout, function () use ($client) {
                $client->close();
            });
        }
        public function setTimeout($timeout) {
            $this->timeout = $timeout;
        }
        public function getTimeout() {
            return $this->timeout;
        }
    }
}
