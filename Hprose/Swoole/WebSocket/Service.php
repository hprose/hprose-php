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
 * Hprose/Swoole/WebSocket/Service.php                    *
 *                                                        *
 * hprose swoole websocket service library for php 5.3+   *
 *                                                        *
 * LastModified: Apr 19, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\WebSocket {
    class Service extends \Hprose\Swoole\Http\Service {
        private function ws_handle($server, $fd, $data) {
            $context = new \stdClass();
            $context->server = $server;
            $context->fd = $fd;
            $context->userdata = new \stdClass();
            $self = $this;
            $id = substr($data, 0, 4);
            $data = substr($data, 4);

            set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($self, $context, $id) {
                if ($self->debug) {
                    $errstr .= " in $errfile on line $errline";
                }
                $error = $self->getErrorTypeString($errno) . ": " . $errstr;
                $data = $self->sendError($error, $context);
                $context->server->push($context->fd, $id . $data, true);
            }, $this->error_types);

            ob_start(function ($data) use ($self, $context, $id) {
                $match = array();
                if (preg_match('/<b>.*? error<\/b>:(.*?)<br/', $data, $match)) {
                    if ($self->debug) {
                        $error = preg_replace('/<.*?>/', '', $match[1]);
                    }
                    else {
                        $error = preg_replace('/ in <b>.*<\/b>$/', '', $match[1]);
                    }
                    $data = $self->sendError(trim($error), $context);
                    $context->server->push($context->fd, $id . $data, true);
                }
            });
            ob_implicit_flush(0);

            $result = $this->defaultHandle($data, $context);

            ob_clean();
            ob_end_flush();
            restore_error_handler();
            $server->push($fd, $id . $result, true);
        }
        public function set_ws_handle($server) {
            $self = $this;
            $buffers = array();
            $server->on('open', function ($server, $request) use (&$buffers) {
                if (isset($buffers[$request->fd])) {
                    unset($buffers[$request->fd]);
                }
            });
            $server->on('close', function ($server, $fd) use (&$buffers) {
                if (isset($buffers[$fd])) {
                    unset($buffers[$fd]);
                }
            });
            $server->on('message', function($server, $frame) use (&$buffers, $self) {
                if (isset($buffers[$frame->fd])) {
                    if ($frame->finish) {
                        $data = $buffers[$frame->fd] . $frame->data;
                        unset($buffers[$frame->fd]);
                        $self->ws_handle($server, $frame->fd, $data);
                    }
                    else {
                        $buffers[$frame->fd] .= $frame->data;
                    }
                }
                else {
                    if ($frame->finish) {
                        $self->ws_handle($server, $frame->fd, $frame->data);
                    }
                    else {
                        $buffers[$frame->fd] = $frame->data;
                    }
                }
            });
        }
    }

    class Server extends Service {
        private $ws;
        public function __construct($host, $port) {
            $this->ws = new \swoole_websocket_server($host, $port);
        }
        public function set($setting) {
            $this->ws->set($setting);
        }
        public function addListener($host, $port) {
            $this->ws->addListener($host, $port);
        }
        public function start() {
            $this->set_ws_handle($this->ws);
            $this->ws->on('request', array($this, 'handle'));
            $this->ws->start();
        }
    }
}
