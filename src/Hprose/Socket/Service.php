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
 * Hprose/Socket/Service.php                              *
 *                                                        *
 * hprose socket Service library for php 5.3+             *
 *                                                        *
 * LastModified: Dec 21, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Socket;

use stdClass;
use Exception;
use Throwable;

class Service extends \Hprose\Service {
    public $onAccept = null;
    public $onClose = null;
    public $onError = null;
    public $readBuffer = 8192;
    public $writeBuffer = 8192;
    private $readableSockets = array();
    private $writeableSockets = array();
    private $onReceives = array();
    private $onSends = array();
    private $deferTasks = array();
    private $delayTasks = array();
    private $delayId = 0;
    private $deadlines = array();
    public function __construct() {
        parent::__construct();
        $this->timer = new Timer($this);
    }
    public function getReadBuffer() {
        return $this->readBuffer;
    }
    public function setReadBuffer($size) {
        $this->readBuffer = $size;
    }
    public function getWriteBuffer() {
        return $this->writeBuffer;
    }
    public function setWriteBuffer($size) {
        $this->writeBuffer = $size;
    }
    public function defer($callback) {
        $this->deferTasks[] = $callback;
    }
    private function runDeferTasks() {
        $tasks = $this->deferTasks;
        $this->deferTasks = array();
        foreach ($tasks as $task) {
            call_user_func($task);
        }
    }
    private function nextDelayId($id) {
        do {
            if ($id >= 0x7FFFFFFF) {
                $id = 0;
            }
            else {
                $id++;
            }
        } while (isset($this->delayTasks[$id]) &&
                is_callable($this->delayTasks[$id]));
        return $id;
    }
    public function after($delay, $callback) {
        $id = $this->delayId;
        $this->deadlines[$id] = ($delay / 1000) + microtime(true);
        $this->delayTasks[$id] = array($callback, true);
        $this->delayId = $this->nextDelayId($id);
        return $id;
    }
    public function tick($delay, $callback) {
        $id = $this->delayId;
        $this->deadlines[$id] = ($delay / 1000) + microtime(true);
        $deadlines = &$this->deadlines;
        $this->delayTasks[$id] = array(function() use ($id, &$deadlines, $delay, $callback) {
            $deadlines[$id] = ($delay / 1000) + microtime(true);
            call_user_func($callback);
        }, false);
        $this->delayId = $this->nextDelayId($id);
        return $id;
    }
    public function clear($id) {
        unset($this->delayTasks[$id]);
        unset($this->deadlines[$id]);
    }
    private function runDelayTasks() {
        foreach ($this->deadlines as $id => $deadline) {
            if (microtime(true) >= $deadline) {
                list($task, $once) = $this->delayTasks[$id];
                call_user_func($task);
                if ($once) {
                    unset($this->delayTasks[$id]);
                    unset($this->deadlines[$id]);
                }
            }
        }
    }
    protected function nextTick($callback) {
        $this->defer($callback);
    }
    public function createContext($server, $socket) {
        $context = new stdClass();
        $context->server = $server;
        $context->socket = $socket;
        $context->userdata = new stdClass();
        return $context;
    }
    public function addSocket(&$sockets, $socket) {
        $index = array_search($socket, $sockets, true);
        if ($index === false) {
            $sockets[] = $socket;
        }
    }
    public function removeSocket(&$sockets, $socket) {
        $index = array_search($socket, $sockets, true);
        if ($index !== false) {
            unset($sockets[$index]);
        }
    }
    private function getOnSend($server, $socket) {
        $self = $this;
        $bytes = '';
        $sockets = &$this->writeableSockets;
        return function($data = '') use ($server, $socket, $self, &$bytes, &$sockets) {
            $bytes .= $data;
            $len = strlen($bytes);
            if ($len === 0) {
                $self->removeSocket($sockets, $socket);
            }
            else {
                $sent = @fwrite($socket, $bytes, $len);
                if ($sent === false) {
                    $self->error($server, $socket, 'Unknown write error');
                }
                elseif ($sent < $len) {
                    $bytes = substr($bytes, $sent);
                    $self->addSocket($sockets, $socket);
                }
                else {
                    $bytes = '';
                    $self->removeSocket($sockets, $socket);
                }
            }
        };
    }
    private function getOnReceive($server, $socket) {
        $self = $this;
        $bytes = '';
        $headerLength = 4;
        $dataLength = -1;
        $id = null;
        $onSend = $this->onSends[(int)$socket];
        $send = function($data, $id) use ($onSend) {
            $dataLength = strlen($data);
            if ($id === null) {
                $onSend(pack("N", $dataLength) . $data);
            }
            else {
                $onSend(pack("NN", $dataLength | 0x80000000, $id) . $data);
            }
        };
        $userFatalErrorHandler = &$this->userFatalErrorHandler;
        return function()
                use ($self, $server, $socket, &$bytes, &$headerLength, &$dataLength, &$id, &$userFatalErrorHandler, $send) {
            $data = @fread($socket, $self->readBuffer);
            if ($data === false) {
                $self->error($server, $socket, 'Unknown read error');
                return;
            }
            elseif ($data === '') {
                if ($bytes == '') {
                    $self->error($server, $socket, null);
                }
                else {
                    $self->error($server, $socket, "$socket closed");
                }
                return;
            }
            $bytes .= $data;
            while (true) {
                $length = strlen($bytes);
                if (($dataLength < 0) && ($length >= $headerLength)) {
                    list(, $dataLength) = unpack('N', substr($bytes, 0, 4));
                    if (($dataLength & 0x80000000) !== 0) {
                        $dataLength &= 0x7FFFFFFF;
                        $headerLength = 8;
                    }
                }
                if (($headerLength === 8) && ($id === null) && ($length >= $headerLength)) {
                    list(, $id) = unpack('N', substr($bytes, 4, 4));
                }
                if (($dataLength >= 0) && (($length - $headerLength) >= $dataLength)) {
                    $context = $self->createContext($server, $socket);
                    $data = substr($bytes, $headerLength, $dataLength);
                    $userFatalErrorHandler = function($error) use ($self, $send, $context, $id) {
                        $send($self->endError($error, $context), $id);
                    };
                    $self->defaultHandle($data, $context)->then(function($data) use ($send, $id) {
                        $send($data, $id);
                    });
                    $bytes = substr($bytes, $headerLength + $dataLength);
                    $id = null;
                    $headerLength = 4;
                    $dataLength = -1;
                }
                else {
                    break;
                }
            }
        };
    }
    private function accept($server) {
        $socket = @stream_socket_accept($server, 0);
        if ($socket === false) return;
        if (@stream_set_blocking($socket, false) === false) {
            $this->error($server, $socket, 'Unkown error');
            return;
        }
        @stream_set_read_buffer($socket, $this->readBuffer);
        @stream_set_write_buffer($socket, $this->writeBuffer);
        $onAccept = $this->onAccept;
        if (is_callable($onAccept)) {
            try {
                $context = $this->createContext($server, $socket);
                call_user_func($onAccept, $context);
            }
            catch (Exception $e) { $this->error($server, $socket, $e); }
            catch (Throwable $e) { $this->error($server, $socket, $e); }
        }
        $this->readableSockets[] = $socket;
        $this->onSends[(int)$socket] = $this->getOnSend($server, $socket);
        $this->onReceives[(int)$socket] = $this->getOnReceive($server, $socket);
    }
    private function read($socket) {
        if (isset($this->onReceives[(int)$socket])) {
            $onReceive = $this->onReceives[(int)$socket];
            $onReceive();
        }
    }
    private function write($socket) {
        if (isset($this->onSends[(int)$socket])) {
            $onSend = $this->onSends[(int)$socket];
            $onSend();
        }
    }
    private function close($socket, $context) {
        $this->removeSocket($this->writeableSockets, $socket);
        $this->removeSocket($this->readableSockets, $socket);
        unset($this->onReceives[(int)$socket]);
        unset($this->onSends[(int)$socket]);
        @stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
        $onClose = $this->onClose;
        if (is_callable($onClose)) {
            try {
                call_user_func($onClose, $context);
            }
            catch (Exception $e) {}
            catch (Throwable $e) {}
        }
    }
    public function error($server, $socket, $ex) {
        $context = $this->createContext($server, $socket);
        if ($ex !== null) {
            $onError = $this->onError;
            if (is_callable($onError)) {
                if (!($ex instanceof Exception || $ex instanceof Throwable)) {
                    $e = error_get_last();
                    if ($e === null) {
                        $ex = new Exception($ex);
                    }
                    else {
                        $ex = new ErrorException($e['message'], 0, $e['type'], $e['file'], $e['line']);
                    }
                }
                try {
                    call_user_func($onError, $ex, $context);
                }
                catch (Exception $e) {}
                catch (Throwable $e) {}
            }
        }
        $this->close($socket, $context);
    }
    private function timeout() {
        if (empty($this->deferTasks)) {
            $deadlines = $this->deadlines;
            if (empty($deadlines)) {
                return 3600;
            }
            return max(0, min($deadlines) - microtime(true));
        }
        return 0;
    }
    public function handle($servers) {
        $readableSockets = &$this->readableSockets;
        $writeableSockets = &$this->writeableSockets;
        array_splice($readableSockets, 0, 0, $servers);
        while (!empty($readableSockets)) {
            $timeout = $this->timeout();
            $sec = floor($timeout);
            $usec = ($timeout - $sec) * 1000;
            $read = array_values($readableSockets);
            $write = array_values($writeableSockets);
            $except = NULL;
            $n = @stream_select($read, $write, $except, $sec, $usec);
            if ($n === false) {
                foreach ($servers as $server) {
                    $this->error($server, $server, 'Unknown select error');
                }
                break;
            }
            if ($n > 0) {
                foreach ($read as $socket) {
                    if (array_search($socket, $servers, true) !== false) {
                        $this->accept($socket);
                    }
                    else {
                        $this->read($socket);
                    }
                }
                foreach ($write as $socket) {
                    $this->write($socket);
                }
            }
            $this->runDeferTasks();
            $this->runDelayTasks();
        }
    }
}