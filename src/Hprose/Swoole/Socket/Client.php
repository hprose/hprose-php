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
        static private $default_setting = array(
            'open_length_check' => true,
            'package_length_type' => 'N',
            'package_length_offset' => 0,
            'package_body_offset' => 4,
            'open_eof_check' => false,
        );
        public $setting = array();
        private $conn_stats = array();
        private $type = SWOOLE_TCP;
        private $host = "";
        private $port = 0;
        private $timeout = 30000;
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
        public function set($setting) {
            $this->setting = array_replace($this->setting, $setting);
        }
        protected function sendAndReceive($request) {
            $client = new \swoole_client($this->type | SWOOLE_KEEP);
            $setting = array_replace($this->setting, self::$default_setting);
            if (!isset($setting['package_max_length'])) {
                $setting['package_max_length'] = $this->return_bytes(ini_get('memory_limit'));
            }
            if ($setting['package_max_length'] < 0) {
                $setting['package_max_length'] = 0x7fffffff;
            }
            $client->set($setting);
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
            $setting = array_replace($this->setting, self::$default_setting);
            if (!isset($setting['package_max_length'])) {
                $setting['package_max_length'] = $this->return_bytes(ini_get('memory_limit'));
            }
            if ($setting['package_max_length'] < 0) {
                $setting['package_max_length'] = 0x7fffffff;
            }
            $client->set($setting);
            $client->on("connect", function($cli) use ($self, $request, $use) {
                if (!$self->send($cli, $request)) {
                    $self->sendAndReceiveCallback('', new \Exception(socket_strerror($cli->errCode)), $use);
                }
            });
            $client->on("error", function($cli) use ($self, $use) {
                $self->sendAndReceiveCallback('', new \Exception(socket_strerror($cli->errCode)), $use);
            });
            $client->on("receive", function($cli, $data) use ($self, $use) {
                swoole_timer_clear($cli->timer);
                try {
                    $self->sendAndReceiveCallback(substr($data, 4), null, $use);
                }
                catch(\Exception $e) {
                }
                $cli->close();
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
