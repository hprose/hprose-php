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
 * LastModified: Jun 6, 2015                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\WebSocket {
    class Service extends \Hprose\Swoole\Http\Service {
        private function ws_handle($server, $fd, $data) {
            $id = substr($data, 0, 4);
            $data = substr($data, 4);

            $context = new \stdClass();
            $context->server = $server;
            $context->fd = $fd;
            $context->id = $id;
            $context->userdata = new \stdClass();
            $self = $this;

            $this->user_fatal_error_handler = function($error) use ($self, $context) {
                $context->server->push($context->fd, $context->id . $self->sendError($error, $context), true);
            };

            $result = $this->defaultHandle($data, $context);

            if ($result instanceof \Hprose\Future) {
                $result->then(function($result) use ($server, $fd, $id) {
                    $server->push($fd, $id . $result, true);
                });
            }
            else {
                $server->push($fd, $id . $result, true);
            }
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
}
