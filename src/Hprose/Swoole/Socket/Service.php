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
 * LastModified: Jul 20, 2015                             *
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
        public function send($server, $fd, $data) {
            $len = strlen($data);
            if ($len < self::MAX_PACK_LEN - 4) {
                return $server->send($fd, pack("N", $len) . $data);
            }
            if (!$server->send($fd, pack("N", $len))) {
                return false;
            }
            for ($i = 0; $i < $len; $i += self::MAX_PACK_LEN) {
                if (!$server->send($fd, substr($data, $i, min($len - $i, self::MAX_PACK_LEN)))) {
                    return false;
                }
            }
            return true;
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
                $setting['package_max_length'] = self::MAX_PACK_LEN * 4;
            }
            $server->set($setting);
            $server->on("receive", function ($server, $fd, $from_id, $data) use($self) {
                $context = new \stdClass();
                $context->server = $server;
                $context->fd = $fd;
                $context->from_id = $from_id;
                $context->userdata = new \stdClass();

                $self->user_fatal_error_handler = function($error) use ($self, $context) {
                    $self->send($context->server, $context->fd, $self->sendError($error, $context));
                };

                $result = $self->defaultHandle(substr($data, 4), $context);
                if ($result instanceof \Hprose\Future) {
                    $result->then(function($result) use ($self, $server, $fd) { $self->send($server, $fd, $result); });
                }
                else {
                    $self->send($server, $fd, $result);
                }
            });
        }
    }
}
