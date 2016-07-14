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
 * Hprose/Swoole/Socket/HalfDuplexTransporter.php         *
 *                                                        *
 * hprose socket HalfDuplexTransporter class for php 5.3+ *
 *                                                        *
 * LastModified: Jul 14, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Socket;

use stdClass;
use Exception;
use Hprose\TimeoutException;

class HalfDuplexTransporter extends Transporter {
    public function fetch() {
        while (!empty($this->pool)) {
            $conn = array_pop($this->pool);
            if ($conn->isConnected()) {
                swoole_timer_clear($conn->timer);
                $conn->timer = null;
                $conn->wakeup();
                return $conn;
            }
        }
        return null;
    }
    public function recycle($conn) {
        if (array_search($conn, $this->pool, true) === false) {
            $conn->sleep();
            $conn->timer = swoole_timer_after($this->client->poolTimeout, function() use ($conn) {
                swoole_timer_clear($conn->timer);
                if ($conn->isConnected()) {
                    $conn->close();
                }
            });
            $this->pool[] = $conn;
        }
    }
    public function clean($conn) {
        if (isset($conn->timeoutId)) {
            swoole_timer_clear($conn->timeoutId);
            unset($conn->timeoutId);
        }
    }
    public function sendNext($conn) {
        if (!empty($this->requests)) {
            $request = array_pop($this->requests);
            $request[] = $conn;
            call_user_func_array(array($this, "send"), $request);
        }
        else {
            $this->recycle($conn);
        }
    }
    public function send($request, $future, $context, $conn) {
        $self = $this;
        $timeout = $context->timeout;
        if ($timeout > 0) {
            $conn->timeoutId = swoole_timer_after($timeout, function() use ($self, $future, $conn) {
                $self->clean($conn);
                $self->recycle($conn);
                $future->reject(new TimeoutException('timeout'));
            });
        }
        $conn->onreceive = function($conn, $data) use ($self, $future) {
            $self->clean($conn);
            $self->sendNext($conn);
            $future->resolve($data);
        };
        $conn->onclose = function($conn) use ($self, $future) {
            $self->clean($conn);
            $future->reject(new Exception(socket_strerror($conn->errCode)));
        };
        $header = pack('N', strlen($request));
        $conn->send($header);
        $conn->send($request);
    }
    public function sendAndReceive($request, $future, stdClass $context) {
        $conn = $this->fetch();
        if ($conn !== null) {
            $this->send($request, $future, $context, $conn);
        }
        else if ($this->size < $this->client->maxPoolSize) {
            $self = $this;
            $conn = $this->create();
            $conn->onclose = function($conn) use ($self, $future) {
                $self->clean($conn);
                $future->reject(new Exception(socket_strerror($conn->errCode)));
            };
            $conn->on('close', function($conn) {
                $onclose = $conn->onclose;
                $onclose($conn);
            });
            $conn->on('error', function($conn) use ($future) {
                $future->reject(new Exception(socket_strerror($conn->errCode)));
            });
            $conn->on('connect', function($conn) use ($self, $request, $future, $context) {
                var_dump('xxx');
                $self->send($request, $future, $context, $conn);
            });
            $conn->connect($this->client->host, $this->client->port);
        }
        else {
            $this->requests[] = array($request, $future, $context);
        }
    }
}