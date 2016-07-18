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
 * LastModified: Jul 19, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\WebSocket;

use stdClass;
use Hprose\Future;

class Service extends \Hprose\Swoole\Http\Service {
    public $onAccept = null;
    public $onClose = null;
    public function onMessage($server, $fd, $data) {
        $id = substr($data, 0, 4);
        $request = substr($data, 4);

        $context = new stdClass();
        $context->server = $server;
        $context->fd = $fd;
        $context->id = $id;
        $context->userdata = new stdClass();
        $self = $this;

        $this->userFatalErrorHandler = function($error)
                use ($self, $server, $fd, $id, $context) {
            $server->push($fd, $id . $self->endError($error, $context), true);
        };

        $response = $this->defaultHandle($request, $context);

        if (Future\isFuture($response)) {
            $response->then(function($response) use ($server, $fd, $id) {
                $server->push($fd, $id . $response, true);
            });
        }
        else {
            $server->push($fd, $id . $response, true);
        }
    }
    public function wsHandle($server) {
        $self = $this;
        $buffers = array();
        $server->on('open', function ($server, $request) use ($self, &$buffers) {
            $fd = $request->fd;
            if (isset($buffers[$fd])) {
                unset($buffers[$fd]);
            }
            $context = new stdClass();
            $context->server = $server;
            $context->fd = $fd;
            $context->userdata = new stdClass();
            try {
                $onAccept = $self->onAccept;
                if (is_callable($onAccept)) {
                    call_user_func($onAccept, $context);
                }
            }
            catch (Exception $e) { $server->close($fd); }
            catch (Throwable $e) { $server->close($fd); }
        });
        $server->on('close', function ($server, $fd) use ($self, &$buffers) {
            if (isset($buffers[$fd])) {
                unset($buffers[$fd]);
            }
            $context = new stdClass();
            $context->server = $server;
            $context->fd = $fd;
            $context->userdata = new stdClass();
            try {
                $onClose = $self->onClose;
                if (is_callable($onClose)) {
                    call_user_func($onClose, $context);
                }
            }
            catch (Exception $e) {}
            catch (Throwable $e) {}
        });
        $server->on('message', function($server, $frame) use ($self, &$buffers) {
            if (isset($buffers[$frame->fd])) {
                if ($frame->finish) {
                    $data = $buffers[$frame->fd] . $frame->data;
                    unset($buffers[$frame->fd]);
                    $self->onMessage($server, $frame->fd, $data);
                }
                else {
                    $buffers[$frame->fd] .= $frame->data;
                }
            }
            else {
                if ($frame->finish) {
                    $self->onMessage($server, $frame->fd, $frame->data);
                }
                else {
                    $buffers[$frame->fd] = $frame->data;
                }
            }
        });
    }
}
