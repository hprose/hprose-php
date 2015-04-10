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
 * Hprose/Swoole/Service.php                              *
 *                                                        *
 * hprose swoole service library for php 5.3+             *
 *                                                        *
 * LastModified: Apr 10, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole {
    class Service extends \Hprose\Service {
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
                $setting['package_max_length'] = 0x80000000;
            }
            $server->set($setting);
            $server->on("receive", function ($server, $fd, $from_id, $data) use($self) {
                $context = new \stdClass();
                $context->server = $server;
                $context->fd = $fd;
                $context->from_id = $from_id;

                set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($self, $context) {
                    if ($self->debug) {
                        $errstr .= " in $errfile on line $errline";
                    }
                    $error = $self->getErrorTypeString($errno) . ": " . $errstr;
                    $self->send($context->server, $context->fd, $self->sendError($error, $context));
                }, $self->error_types);

                ob_start(function ($data) use ($self, $context) {
                    $match = array();
                    if (preg_match('/<b>.*? error<\/b>:(.*?)<br/', $data, $match)) {
                        if ($self->debug) {
                            $error = preg_replace('/<.*?>/', '', $match[1]);
                        }
                        else {
                            $error = preg_replace('/ in <b>.*<\/b>$/', '', $match[1]);
                        }
                        $data = $self->sendError(trim($error), $context);
                        $self->send($context->server, $context->fd, $data);
                    }
                });
                ob_implicit_flush(0);

                $data = $self->defaultHandle(substr($data, 4), $context);

                ob_clean();
                ob_end_flush();
                restore_error_handler();

                $self->send($server, $fd, $data);
            });
        }
    }
    class Server extends Service {
        private $server;
        public function __construct($host, $port, $mode = SWOOLE_PROCESS, $type = SWOOLE_SOCK_TCP) {
            $this->server = new \swoole_server($host, $port, $mode, $type);
        }
        public function addListener($host, $port, $type = SWOOLE_SOCK_TCP) {
            $this->server->addListener($host, $port, $type);
        }
        public function start() {
            $this->handle($this->server);
            $this->server->start();
        }
    }
}
