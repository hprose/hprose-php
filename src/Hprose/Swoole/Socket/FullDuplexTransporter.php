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
 * LastModified: Jul 14, 2016                             *
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
                if ($this->counts[$conn->sock] === 0) {
                    swoole_timer_clear($this->timers[$conn->sock]);
                    unset($this->timers[$conn->sock]);
                    $conn->wakeup();
                }
                return $conn;
            }
        }
        return null;
    }
    public function init($conn) {
        $self = $this;
        $this->counts[$conn->sock] = 0;
        $this->futures[$conn->sock] = array();
        $this->timeoutIds[$conn->sock] = array();
        $this->receives[$conn->sock] = function($conn, $data, $id) use ($self) {
            if (isset($self->futures[$conn->sock][$id])) {
                $future = $self->futures[$conn->sock][$id];
                $self->clean($conn, $id);
                if ($self->counts[$conn->sock] === 0) {
                    $self->recycle($conn);
                }
                $future->resolve($data);
            }
        };
    }
    public function recycle($conn) {
        $self = $this;
        $conn->sleep();
        $this->timers[$conn->sock] = swoole_timer_after($this->client->poolTimeout, function() use ($self, $conn) {
            swoole_timer_clear($self->timers[$conn->sock]);
            $conn->close();
        });
    }
    public function clean($conn, $id) {
        if (isset($this->timeountIds[$conn->sock][$id])) {
            swoole_timer_clear($this->timeountIds[$conn->sock][$id]);
            unset($this->timeountIds[$conn->sock][$id]);
        }
        unset($this->futures[$conn->sock][$id]);
        $this->counts[$conn->sock]--;
        $this->sendNext($conn);
    }
    public function sendNext($conn) {
        if ($this->counts[$conn->sock] < 10) {
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
            $this->timeoutIds[$conn->sock][$id] = swoole_timer_after($timeout, function() use ($self, $future, $id, $conn) {
                $self->clean($conn, $id);
                if ($self->counts[$conn->sock] === 0) {
                    $self->recycle($conn);
                }
                $future->reject(new TimeoutException('timeout'));
            });
        }
        $this->counts[$conn->sock]++;
        $this->futures[$conn->sock][$id] = $future;
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
            $conn->on('error', function($conn) use ($self, $future) {
                $self->size--;
                $future->reject(new Exception(socket_strerror($conn->errCode)));
            });
            $conn->on('close', function($conn) use ($self) {
                if ($conn->errCode !== 0) {
                    $futures = $self->futures[$conn->sock];
                    $error = new Exception(socket_strerror($conn->errCode));
                    foreach ($futures as $id => $future) {
                        $self->clean($conn, $id);
                        $future->reject($error);
                    }
                }
                $self->size--;
            });
            $conn->on('connect', function($conn) use ($self, $request, $future, $id, $context) {
                $self->init($conn);
                $self->send($request, $future, $id, $context, $conn);
            });
            $conn->connect($this->client->host, $this->client->port);
        }
        else {
            $this->requests[] = array($request, $future, $id, $context);
        }
    }
}