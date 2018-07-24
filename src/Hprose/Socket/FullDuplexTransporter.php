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
 * Hprose/Socket/FullDuplexTransporter.php                *
 *                                                        *
 * hprose socket FullDuplexTransporter class for php 5.3+ *
 *                                                        *
 * LastModified: Jul 24, 2018                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Socket;

class FullDuplexTransporter extends Transporter {
    private static $id = 0;
    private static $idinited = false;
    private function getId() {
        if (self::$idinited === false) {
            self::$idinited = true;
            $min = intval(date('i'), 10);
            $sec = intval(date('s'), 10);
            $msec = microtime(true);
            $msec -= floor($msec);
            self::$id = floor(($min * 60 + $sec + $msec) * 1000) * 1024;
        }
        return self::$id++;
    }
    protected function appendHeader($request, $id = null) {
        if ($id === null) {
            $id = $this->getId();
        }
        return pack("NN", strlen($request) | 0x80000000, $id) . $request;
    }
    protected function createRequest($index, $request) {
        $id = $this->getId();
        $buffer = $this->appendHeader($request, $id);
        return new DataBuffer($index, $buffer, strlen($buffer), $id);
    }
    protected function afterWrite($request, $stream, $o) {
        $response = new DataBuffer($request->index, '', 0, $request->id);
        $stream_id = (integer)$stream;
        unset($o->requests[$stream_id]);
        if (empty($o->queue[$stream_id])) {
            $o->queue[$stream_id] = array();
            $o->readpool[] = $stream;
        }
        $o->queue[$stream_id][$request->id] = $response;
    }
    protected function asyncReadError($o, $stream, $index = -1) {
        $stream_id = (integer)$stream;
        foreach ($o->queue[$stream_id] as $response) {
            $index = $response->index;
            $o->results[$index]->reject($this->getLastError('response read error'));
            $this->free($o, $index);
        }
        unset($o->queue[$stream_id]);
        unset($o->responses[$stream_id]);
        @fclose($stream);
        $this->removeStream($stream, $o->readpool);
        $this->removeStream($stream, $o->writepool);
    }
    private function getHeaderInfo($stream) {
        $header = $this->readHeader($stream, 8);
        if ($header === false) return false;
        list(, $length, $id) = unpack('N*', $header);
        $length &= 0x7FFFFFFF;
        return array($length, $id);
    }
    protected function getBodyLength($stream) {
        $headerInfo = $this->getHeaderInfo($stream);
        if ($headerInfo === false) return false;
        return $headerInfo[0];
    }
    protected function getResponse($stream, $o) {
        $stream_id = (integer)$stream;
        if (isset($o->responses[$stream_id])) {
            $response = $o->responses[$stream_id];
        }
        else {
            $headerInfo = $this->getHeaderInfo($stream);
            if ($headerInfo === false) return false;
            $id = $headerInfo[1];
            if (isset($o->queue[$stream_id][$id])) {
                $response = $o->queue[$stream_id][$id];
            }
            else {
                $response = new DataBuffer(-1, '', 0, $id);
            }
            $response->length = $headerInfo[0];
            $o->responses[$stream_id] = $response;
        }
        return $response;
    }
    protected function afterRead($stream, $o, $response) {
        $stream_id = (integer)$stream;
        if (isset($o->queue[$stream_id][$response->id])) {
            unset($o->queue[$stream_id][$response->id]);
        }
        if (empty($o->queue[$stream_id])) {
            $this->removeStream($stream, $o->readpool);
        }
    }
}