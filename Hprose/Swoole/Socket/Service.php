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
 * Hprose/Swoole/Socket/Service.php                       *
 *                                                        *
 * hprose swoole socket service library for php 5.3+      *
 *                                                        *
 * LastModified: Apr 20, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Socket {
    class Service extends \Hprose\Base\Service {
        const MAX_PACK_LEN = 0x200000;
        static private $default_setting = array(
            'open_length_check' => true,
            'package_length_type' => 'N',
            'package_length_offset' => 0,
            'package_body_offset' => 4,
            'open_eof_check' => false,
        );
        public $setting = array();
        private function send($server, $fd, $data) {
            $len = strlen($data);
            if ($len < self::MAX_PACK_LEN - 4) {
                return $server->send($fd, pack("N", $len) . $data);
            }
            if (!$server->send($fd, pack("N", $len))) {
                return false;
            }
            for ($i = 0; $i < $len; ++$i) {
                if (!$server->send($fd, substr($data, $i, min($len - $i, self::MAX_PACK_LEN)))) {
                    return false;
                }
                $i += self::MAX_PACK_LEN;
            }
            return true;
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
        public function set($setting) {
            $this->setting = array_replace($this->setting, $setting);
        }
        public function handle($server) {
            $self = $this;
            $setting = array_replace($this->setting, self::$default_setting);
            if (!isset($setting['package_max_length'])) {
                $setting['package_max_length'] = $this->return_bytes(ini_get('memory_limit'));
            }
            if ($setting['package_max_length'] < 0) {
                $setting['package_max_length'] = 0x7fffffff;
            }
            $server->set($setting);
            $server->on("receive", function ($server, $fd, $from_id, $data) use($self) {
                $context = new \stdClass();
                $context->server = $server;
                $context->fd = $fd;
                $context->from_id = $from_id;
                $context->userdata = new \stdClass();

                $this->user_fatal_error_handler = function($error) use ($self, $context) {
                    @ob_end_clean();
                    $self->send($context->server, $context->fd, $self->sendError($error, $context));
                };

                $self->send($server, $fd, $self->defaultHandle(substr($data, 4), $context));
            });
        }
    }
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
                        $result->port = 0;
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
