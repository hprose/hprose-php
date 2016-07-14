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
 * Hprose/Socket/Transporter.php                          *
 *                                                        *
 * hprose socket Transporter class for php 5.3+           *
 *                                                        *
 * LastModified: Jul 12, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Socket;

use stdClass;
use Exception;
use Hprose\TimeoutException;

class FullDuplexTransporter extends Transporter {
    private $nextid = 0;
    public function fetch() {
        while (!empty($this->pool)) {
            $conn = array_pop($this->pool);
            if ($conn->isConnected()) {
                if ($conn->count === 0) {
                    swoole_timer_clear($conn->timer);
                    $conn->timer = null;
                    $conn->wakeup();
                }
                return $conn;
            }
        }
        return null;
    }
    public function init($conn) {
        $self = $this;
        $conn->count = 0;
        $conn->futures = array();
        $conn->timeoutIds = array();
        $conn->receive = function($conn, $data, $id) use ($self) {
            if (isset($conn->futures[$id])) {
                $future = $conn->futures[$id];
                $self->clean($conn, $id);
                if ($conn->count === 0) {
                    $self->recycle($conn);
                }
                $future->resolve($data);
            }
        };
        $conn->on('close', function($conn) use ($self) {
            if ($conn->errCode !== 0) {
                $futures = $conn->futures;
                $error = new Exception(socket_strerror($conn->errCode));
                foreach ($futures as $id => $future) {
                    $self->clean($conn, $id);
                    $future->reject($error);
                }
            }
            $self->size--;
        });
    }
    public function recycle($conn) {
        $conn->sleep();
        $conn->timer = swoole_timer_after($this->client->poolTimeout, function() use ($conn) {
            swoole_timer_clear($conn->timer);
            if ($conn->isConnected()) {
                $conn->close();
            }
        });
    }
    public function clean($conn, $id) {
        if (isset($conn->timeountIds[$id])) {
            swoole_timer_clear($conn->timeountIds[$id]);
            unset($conn->timeountIds[$id]);
        }
        unset($conn->futures[$id]);
        $conn->count--;
        $this->sendNext($conn);
    }
    public function sendNext($conn) {
        if ($conn->count < 10) {
            if (!empty($this->requests)) {
                $request = array_pop($this->requests);
                $request[] = $conn;
                call_user_func_array(array($this, "send"), $request);
            }
            else {
                if (array_search($conn, $this->pool, true) === false) {
                    $this->pool[] = $conn;
                }
            }
        }
    }
    public function send($request, $future, $id, $context, $conn) {
        $self = $this;
        $timeout = $context->timeout;
        if ($timeout > 0) {
            $conn->timeoutIds[$id] = swoole_timer_after($timeout, function() use ($self, $future, $id, $conn) {
                $self->clean($conn, $id);
                if ($conn->count === 0) {
                    $self->recycle($conn);
                }
                $future->reject(new TimeoutException('timeout'));
            });
        }
        $conn->count++;
        $conn->futures[$id] = $future;
        $header = pack('NN', strlen($request) | 0x80000000, $id);
        $conn->send($header);
        $conn->send($request);
        $this->sendNext($conn);
    }
    public function getNextId() {
        return ($this->nextid < 0x7FFFFFFF) ? $this->nextid++ : $this->nextid = 0;
    }
    public function sendAndReceive($request, $future, stdClass $context) {
        $conn = $this->fetch();
        $id = $this->getNextId();
        if ($conn !== null) {
            $this->send($request, $future, $id, $context, $conn);
        }
        else if ($this->size < $this->client->maxPoolSize) {
            $self = $this;
            $conn = $this->create();
            $self->init($conn);
            $conn->on('error', function($conn) use ($self, $future) {
                $self->size--;
                $future->reject(new Exception(socket_strerror($conn->errCode)));
            });
            $conn->on('connect', function($conn) use ($self, $request, $future, $id, $context) {
                $self->send($request, $future, $id, $context, $conn);
            });
            $conn->connect($this->client->host, $this->client->port);
        }
        else {
            $this->requests[] = array($request, $future, $id, $context);
        }
    }
}