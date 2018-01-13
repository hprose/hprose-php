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
 * Hprose/Socket/HalfDuplexTransporter.php                *
 *                                                        *
 * hprose socket HalfDuplexTransporter class for php 5.3+ *
 *                                                        *
 * LastModified: Jan 14, 2018                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Socket;

class HalfDuplexTransporter extends Transporter {
    protected function appendHeader($request) {
        return pack("N", strlen($request)) . $request;
    }
    protected function createRequest($index, $request) {
        $buffer = $this->appendHeader($request);
        return new DataBuffer($index, $buffer, strlen($buffer));
    }
    protected function afterWrite($request, $stream, $o) {
        $stream_id = (integer)$stream;
        $o->responses[$stream_id] = new DataBuffer($request->index, '', 0);
        $o->readpool[] = $stream;
        unset($o->requests[$stream_id]);
        $this->removeStream($stream, $o->writepool);
    }
    protected function asyncReadError($o, $stream, $index) {
        if (isset($o->results[$index])) {
            $o->results[$index]
              ->reject($this->getLastError('response read error'));
            $this->free($o, $index);
        }
        unset($o->responses[(integer)$stream]);
        fclose($stream);
        $this->removeStream($stream, $o->readpool);
    }
    protected function getBodyLength($stream) {
        $header = $this->readHeader($stream, 4);
        if ($header === false) return false;
        list(, $length) = unpack('N', $header);
        return $length;
    }
    protected function getResponse($stream, $o) {
        $stream_id = (integer)$stream;
        $response = $o->responses[$stream_id];
        if ($response->length === 0) {
            $length = $this->getBodyLength($stream);
            $response->length = $length;
        }
        return $response;
    }
    protected function afterRead($stream, $o, $response) {
        if ($o->current < $o->count) {
            $o->writepool[] = $stream;
        }
        $this->removeStream($stream, $o->readpool);
    }
}