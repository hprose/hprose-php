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
 * LastModified: Dec 20, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Socket;

use stdClass;
use Exception;
use ErrorException;
use Hprose\Future;
use Hprose\TimeoutException;

abstract class Transporter {
    private $client;
    private $requests = array();
    private $deadlines = array();
    private $results = array();
    private $stream = null;
    private $async;
    protected abstract function appendHeader($request);
    protected abstract function createRequest($index, $request);
    protected abstract function afterWrite($request, $stream, $o);
    protected abstract function getBodyLength($stream);
    protected abstract function asyncReadError($o, $stream, $index);
    protected abstract function getResponse($stream, $o);
    protected abstract function afterRead($stream, $o, $response);

    public function __construct(Client $client, $async) {
        $this->client = $client;
        $this->async = $async;
    }
    public function __destruct() {
        if ($this->stream !== null) @fclose($this->stream);
    }
    protected function getLastError($error) {
        $e = error_get_last();
        if ($e === null) {
            return new Exception($error);
        }
        else {
            return new ErrorException($e['message'], 0, $e['type'], $e['file'], $e['line']);
        }
    }
    protected function removeStream($stream, &$pool) {
        $index = array_search($stream, $pool, true);
        if ($index !== false) {
            unset($pool[$index]);
        }
    }
    protected function readHeader($stream, $n) {
        $header = '';
        do {
            $buffer = @fread($stream, $n - strlen($header));
            $header .= $buffer;
        } while (!empty($buffer) && (strlen($header) < $n));
        if (strlen($header) < $n) {
            return false;
        }
        return $header;
    }
    protected function free($o, $index) {
        unset($o->results[$index]);
        unset($o->deadlines[$index]);
        unset($o->buffers[$index]);
    }
    protected function asyncWrite($stream, $o) {
        $stream_id = (integer)$stream;
        if (isset($o->requests[$stream_id])) {
            $request = $o->requests[$stream_id];
        }
        else {
            if ($o->current < $o->count) {
                $request = $this->createRequest($o->current, $o->buffers[$o->current]);
                $o->requests[$stream_id] = $request;
                unset($o->buffers[$o->current]);
                $o->current++;
            }
            else {
                $this->removeStream($stream, $o->writepool);
                return;
            }
        }
        $sent = @fwrite($stream, $request->buffer, $request->length);
        if ($sent === false) {
            $o->results[$request->index]->reject($this->getLastError('request write error'));
            $this->free($o, $request->index);
            @fclose($stream);
            $this->removeStream($stream, $o->writepool);
            return;
        }
        if ($sent < $request->length) {
            $request->buffer = substr($request->buffer, $sent);
            $request->length -= $sent;
        }
        else {
            $this->afterWrite($request, $stream, $o);
        }
    }
    private function asyncRead($stream, $o) {
        $response = $this->getResponse($stream, $o);
        if ($response === false) {
            $this->asyncReadError($o, $stream, -1);
            return;
        }
        if ($response->length === false) {
            $this->asyncReadError($o, $stream, $response->index);
            return;
        }
        $remaining = $response->length - strlen($response->buffer);
        $buffer = @fread($stream, $remaining);
        if (empty($buffer)) {
            $this->asyncReadError($o, $stream, $response->index);
            return;
        }
        $response->buffer .= $buffer;
        if (strlen($response->buffer) === $response->length) {
            if (isset($o->results[$response->index])) {
                $result = $o->results[$response->index];
                $this->free($o, $response->index);
            }
            $stream_id = (integer)$stream;
            unset($o->responses[$stream_id]);
            $this->afterRead($stream, $o, $response);
            if (isset($result)) {
                $result->resolve($response->buffer);
            }
        }
    }
    private function removeStreamById($stream_id, &$pool) {
        foreach ($pool as $index => $stream) {
            if ((integer)$stream == $stream_id) {
                @fclose($stream);
                unset($pool[$index]);
                return;
            }
        }
    }
    private function closeTimeoutStream($o, $index) {
        foreach ($o->requests as $stream_id => $request) {
            if ($request->index == $index) {
                unset($o->requests[$stream_id]);
                if (!$this->client->fullDuplex) {
                    $this->removeStreamById($stream_id, $o->writepool);
                }
            }
        }
        foreach ($o->responses as $stream_id => $response) {
            if ($response->index == $index) {
                unset($o->responses[$stream_id]);
                if (!$this->client->fullDuplex) {
                    $this->removeStreamById($stream_id, $o->readpool);
                }
            }
        }
    }
    private function checkTimeout($o) {
        foreach ($o->deadlines as $index => $deadline) {
            if (microtime(true) > $deadline) {
                $result = $o->results[$index];
                $this->free($o, $index);
                $this->closeTimeoutStream($o, $index);
                $result->reject(new TimeoutException("timeout"));
            }
        }
    }
    private function createPool($client, $o) {
        $n = min(count($o->results), $client->maxPoolSize);
        $pool = array();
        $errno = 0;
        $errstr = '';
        $context = @stream_context_create($client->options);
        for ($i = 0; $i < $n; $i++) {
            $scheme = parse_url($client->uri, PHP_URL_SCHEME);
            if ($scheme == 'unix') {
                $stream = @pfsockopen('unix://' . parse_url($client->uri, PHP_URL_PATH));
            }
            else {
                $stream = @stream_socket_client(
                    $client->uri . '/' . $i,
                    $errno,
                    $errstr,
                    max(0, $o->deadlines[$i] - microtime(true)),
                    STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT,
                    $context
                );
            }
            if (($stream !== false) &&
                (@stream_set_blocking($stream, false) !== false)) {
                @stream_set_read_buffer($stream, $client->readBuffer);
                @stream_set_write_buffer($stream, $client->writeBuffer);
                if (function_exists('socket_import_stream')) {
                    if (($scheme === 'tcp') || ($scheme === 'unix')) {
                        $socket = socket_import_stream($stream);
                        socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, (int)$client->keepAlive);
                        if ($scheme === 'tcp') {
                            socket_set_option($socket, SOL_TCP, TCP_NODELAY, (int)$client->noDelay);
                        }
                    }
                }
                $pool[] = $stream;
            }
        }
        if (empty($pool)) {
            $e = new Exception($errstr, $errno);
            $results = $o->results;
            $o->buffers = array();
            $o->deadlines = array();
            $o->results = array();
            foreach ($results as $result) {
                $result->reject($e);
            }
            return false;
        }
        return $pool;
    }
    public function loop() {
        $client = $this->client;
        while (count($this->results) > 0) {
            $pool = $this->createPool($client, $this);
            if ($pool === false) continue;
            $o = new stdClass();
            $o->current = 0;
            $o->count = count($this->results);
            $o->responses = array();
            $o->requests = array();
            $o->readpool = array();
            $o->writepool = $pool;
            $o->buffers = $this->buffers;
            $o->deadlines = $this->deadlines;
            $o->results = $this->results;
            $this->buffers = array();
            $this->deadlines = array();
            $this->results = array();
            while (count($o->results) > 0) {
                $read = array_values($o->readpool);
                $write = array_values($o->writepool);
                $except = null;
                $timeout = max(0, min($o->deadlines) - microtime(true));
                $tv_sec = floor($timeout);
                $tv_usec = ($timeout - $tv_sec) * 1000;
                $n = @stream_select($read, $write, $except, $tv_sec, $tv_usec);
                if ($n === false) {
                    $e = $this->getLastError('unkown io error.');
                    foreach ($o->results as $result) {
                        $result->reject($e);
                    }
                    $o->results = array();
                }
                if ($n > 0) {
                    foreach ($write as $stream) $this->asyncWrite($stream, $o);
                    foreach ($read as $stream) $this->asyncRead($stream, $o);
                }
                $this->checkTimeout($o);
                if (count($o->results) > 0 &&
                    count($o->readpool) + count($o->writepool) === 0) {
                    $o->writepool = $this->createPool($client, $o);
                }
            }
            foreach ($o->writepool as $stream) @fclose($stream);
            foreach ($o->readpool as $stream) @fclose($stream);
        }
    }
    public function asyncSendAndReceive($buffer, stdClass $context) {
        $deadline = ($context->timeout / 1000) + microtime(true);
        $result = new Future();
        $this->buffers[] = $buffer;
        $this->deadlines[] = $deadline;
        $this->results[] = $result;
        return $result;
    }
    private function write($stream, $request) {
        $buffer = $this->appendHeader($request);
        $length = strlen($buffer);
        while (true) {
            $sent = @fwrite($stream, $buffer, $length);
            if ($sent === false) {
                return false;
            }
            if ($sent < $length) {
                $buffer = substr($buffer, $sent);
                $length -= $sent;
            }
            else {
                return true;
            }
        }
    }
    private function read($stream) {
        $length = $this->getBodyLength($stream);
        if ($length === false) return false;
        $response = '';
        while (($remaining = $length - strlen($response)) > 0) {
            $buffer = @fread($stream, $remaining);
            if ($buffer === false) {
                return false;
            }
            $response .= $buffer;
        }
        return $response;
    }
    public function syncSendAndReceive($buffer, stdClass $context) {
        $client = $this->client;
        $timeout = ($context->timeout / 1000);
        $sec = floor($timeout);
        $usec = ($timeout - $sec) * 1000;
        $trycount = 0;
        $errno = 0;
        $errstr = '';
        while ($trycount <= 1) {
            $scheme = parse_url($client->uri, PHP_URL_SCHEME);
            if ($this->stream === null) {
                if ($scheme == 'unix') {
                    $this->stream = @pfsockopen('unix://' . parse_url($client->uri, PHP_URL_PATH));
                }
                else {
                    $this->stream = @stream_socket_client(
                        $client->uri,
                        $errno,
                        $errstr,
                        $timeout,
                        STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT,
                        stream_context_create($client->options)
                    );
                }
                if ($this->stream === false) {
                    $this->stream = null;
                    throw new Exception($errstr, $errno);
                }
            }
            $stream = $this->stream;
            @stream_set_read_buffer($stream, $client->readBuffer);
            @stream_set_write_buffer($stream, $client->writeBuffer);
            if (function_exists('socket_import_stream')) {
                if (($scheme === 'tcp') || ($scheme === 'unix')) {
                    $socket = socket_import_stream($stream);
                    socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, (int)$client->keepAlive);
                    if ($scheme === 'tcp') {
                        socket_set_option($socket, SOL_TCP, TCP_NODELAY, (int)$client->noDelay);
                    }
                }
            }
            if (@stream_set_timeout($stream, $sec, $usec) == false) {
                if ($trycount > 0) {
                    throw $this->getLastError("unknown error");
                }
                $trycount++;
            }
            else {
                break;
            }
        }
        if ($this->write($stream, $buffer) === false) {
            throw $this->getLastError("request write error");
        }
        $response = $this->read($stream, $buffer);
        if ($response === false) {
            throw $this->getLastError("response read error");
        }
        return $response;
    }
    public function sendAndReceive($buffer, stdClass $context) {
        if ($this->async) {
            return $this->asyncSendAndReceive($buffer, $context);
        }
        return $this->syncSendAndReceive($buffer, $context);
    }
}
