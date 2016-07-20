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
 * Hprose/Swoole/WebSocket/Client.php                     *
 *                                                        *
 * hprose swoole websocket client library for php 5.3+    *
 *                                                        *
 * LastModified: Jul 20, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\WebSocket;

use stdClass;
use Exception;
use Hprose\Future;
use Hprose\TimeoutException;
use swoole_http_client;

class Client extends \Hprose\Client {
    public $type;
    public $host = '';
    public $ip = '';
    public $port = 80;
    public $ssl = false;
    public $keepAlive = true;
    public $keepAliveTimeout = 300;
    private $header = array();
    private $id = 0;
    private $count = 0;
    private $futures = array();
    private $requests = array();
    private $ready = null;
    private $connecting = false;
    private $ws = null;
    public function __construct($uris = null) {
        parent::__construct($uris);
    }
    public function setHeader($name, $value) {
        $lname = strtolower($name);
        if ($lname != 'content-type' &&
            $lname != 'content-length' &&
            $lname != 'host') {
            if ($value) {
                $this->header[$name] = $value;
            }
            else {
                unset($this->header[$name]);
            }
        }
    }
    public function setKeepAlive($keepAlive = true) {
        $this->keepAlive = $keepAlive;
        $this->header['Connection'] = $keepAlive ? 'keep-alive' : 'close';
        if ($keepAlive) {
            $this->header['Keep-Ailve'] = $this->keepAliveTimeout;
        }
        else {
            unset($this->header['Keep-Ailve']);
        }
    }
    public function getKeepAlive() {
        return $this->keepAlive;
    }
    public function setKeepAliveTimeout($timeout) {
        $this->keepAliveTimeout = $timeout;
        if ($this->keepAlive) {
            $this->header['Keep-Ailve'] = $timeout;
        }
    }
    public function getKeepAliveTimeout() {
        return $this->keepAliveTimeout;
    }
    public function getHost() {
        return $this->host;
    }
    public function getPort() {
        return $this->port;
    }
    public function isSSL() {
        return $this->ssl;
    }
    protected function setUri($uri) {
        parent::setUri($uri);
        $p = parse_url($uri);
        if ($p) {
            switch (strtolower($p['scheme'])) {
                case 'ws':
                    $this->host = $p['host'];
                    $this->port = isset($p['port']) ? $p['port'] : 80;
                    $this->path = isset($p['path']) ? $p['path'] : '/';
                    $this->ssl = false;
                    break;
                case 'wss':
                    $this->host = $p['host'];
                    $this->port = isset($p['port']) ? $p['port'] : 443;
                    $this->path = isset($p['path']) ? $p['path'] : '/';
                    $this->ssl = true;
                    break;
                default:
                    throw new Exception("Only support ws and wss scheme");
            }
        }
        else {
            throw new Exception("Can't parse this uri: " . $uri);
        }
        $this->header['Host'] = $this->host;
        $this->header['Connection'] = $this->keepAlive ? 'keep-alive' : 'close';
        if ($this->keepAlive) {
            $this->header['Keep-Ailve'] = $this->keepAliveTimeout;
        }
        if (filter_var($this->host, FILTER_VALIDATE_IP) === false) {
            $ip = gethostbyname($this->host);
            if ($ip === $this->host) {
                $onError = $this->onError;
                if (is_callable($onError)) {
                    call_user_func($onError, 'gethostbyname', 'dns lookup failed');
                }
            }
            else {
                $this->ip = $ip;
            }
        }
        else {
            $this->ip = $this->host;
        }
    }
    public function close() {
        if ($this->ws !== null && $this->ws->isConnected()) {
            $this->ws->close();
            $this->ws = null;
        }
    }
    protected function wait($interval, $callback) {
        $future = new Future();
        swoole_timer_after($interval * 1000, function() use ($future, $callback) {
            Future\sync($callback)->fill($future);
        });
        return $future;
    }
    private function getNextId() {
        return ($this->id < 0x7FFFFFFF) ? ++$this->id : $this->id = 0;
    }
    private function connect() {
        $this->connecting = true;
        $connecting = &$this->connecting;
        $this->ready = new Future();
        $ready = &$this->ready;
        $futures = &$this->futures;
        $count = &$this->count;
        $requests = &$this->requests;
        $ws = new swoole_http_client($this->ip, $this->port, $this->ssl);
        $ws->on('error', function($ws) use (&$futures, &$count) {
            $error = new Exception(socket_strerror($ws->errCode));
            foreach ($futures as $future) {
                $future->reject($error);
            }
            $futures = array();
            $count = 0;
        });
        $buffer = '';
        $self = $this;
        $ws->on('message', function($ws, $frame) use ($self, &$buffer, &$futures, &$count, &$requests, &$ready) {
            if ($frame->finish) {
                $data = $buffer . $frame->data;
                $buffer = '';
                list(, $id) = unpack('N', substr($data, 0, 4));
                if (isset($futures[$id])) {
                    $future = $futures[$id];
                    unset($futures[$id]);
                    --$count;
                    $future->resolve(substr($data, 4));
                }
                if (($count < 100) && count($requests) > 0) {
                    ++$count;
                    $request = array_pop($requests);
                    $ready->then(function() use ($ws, $request, &$futures) {
                        $id = $request[0];
                        $data = pack('N', $id) . $request[1];
                        if ($ws->push($data, WEBSOCKET_OPCODE_BINARY, true) === false) {
                            if (isset($futures[$id])) {
                                $error = new Exception(socket_strerror($ws->errCode));
                                $futures[$id]->reject($error);
                            }
                        }
                    });
                }
                if ($count === 0) {
                    if (!$self->keepAlive) $ws->close();
                }
            }
            else {
                $buffer .= $frame->data;
            }
        });
        $ws->set(array('keep_alive' => $this->keepAlive,
                        'timeout' => $this->timeout / 1000));
        $ws->setHeaders($this->header);
        $this->ws = $ws;
        $this->ws->upgrade($this->path, function() use (&$connecting, &$ready) {
            $connecting = false;
            $ready->resolve(null);
        });
    }
    protected function sendAndReceive($request, stdClass $context) {
        if (!$this->connecting && ($this->ws === null || !$this->ws->isConnected())) {
            $this->connect();
        }
        $future = new Future();
        $ws = $this->ws;
        $id = $this->getNextId();
        $count = &$this->count;
        $futures = &$this->futures;
        $futures[$id] = $future;
        if ($context->timeout > 0) {
            $timeoutFuture = new Future();
            $timer = swoole_timer_after($context->timeout, function() use ($timeoutFuture) {
                $timeoutFuture->reject(new TimeoutException('timeout'));
            });
            $future->whenComplete(function() use ($timer) {
                swoole_timer_clear($timer);
            })->fill($timeoutFuture);
            $future = $timeoutFuture->catchError(function($e) use (&$count, &$futures, $id) {
                unset($futures[$id]);
                --$count;
                throw $e;
            }, function($e) {
                return $e instanceof TimeoutException;
            });
            
        }
        if ($count < 100) {
            ++$count;
            $this->ready->then(function() use ($ws, $id, $request, &$futures) {
                $data = pack('N', $id) . $request;
                if ($ws->push($data, WEBSOCKET_OPCODE_BINARY, true) === false) {
                    if (isset($futures[$id])) {
                        $error = new Exception(socket_strerror($ws->errCode));
                        $futures[$id]->reject($error);
                    }
                }
            });
        }
        else {
            $this->requests[] = array($id, $request);
        }
       if ($context->oneway) {
            $future->resolve(null);
        }
        return $future;
    }
}
