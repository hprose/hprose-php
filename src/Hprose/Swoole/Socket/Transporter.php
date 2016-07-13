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

use swoole_client;

abstract class Transporter {
    public $client;
    public $uri;
    public $size = 0;
    public $pool = array();
    public $receives = array();
    public $futures = array();
    public $timeoutIds = array();
    public $counts = array();
    public $timers = array();
    public $requests = array();
    public function __construct(Client $client) {
        $this->client = $client;
        $this->uri = $client->uri;
    }
    public function close() {
        foreach ($this->pool as $conn) {
            if (isset($this->timers[$conn->sock])) {
                swoole_timer_clear($this->timers[$conn->sock]);
                unset($this->timers[$conn->sock]);
            }
            if ($conn->isConnected()) {
                $conn->close();
            }
        }
    }
    public function setReceiveEvent($conn) {
        $self = $this;
        $bytes = '';
        $headerLength = 4;
        $dataLength = -1;
        $id = null;
        $conn->on('receive', function($conn, $chunk) use ($self, &$bytes, &$headerLength, &$dataLength, &$id) {
            $bytes .= $chunk;
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
                    $receive = $self->receives[$conn->sock];
                    $receive($conn, substr($bytes, $headerLength, $dataLength), $id);
                    $bytes = substr($bytes, $headerLength + $dataLength);
                    $id = null;
                    $headerLength = 4;
                    $dataLength = -1;
                }
                else {
                    break;
                }
            }
        });
    }
    public function create() {
        $client = $this->client;
        $conn = new swoole_client($client->type, SWOOLE_SOCK_ASYNC);
        if (!empty($client->settings)) {
            $conn->set($client->settings);
        }
        $this->setReceiveEvent($conn);
        $this->size++;
        return $conn;
    }
}