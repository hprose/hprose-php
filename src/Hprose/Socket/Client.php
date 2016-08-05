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
 * Hprose/Socket/Client.php                               *
 *                                                        *
 * hprose socket client class for php 5.3+                *
 *                                                        *
 * LastModified: Aug 6, 2016                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Socket;

use stdClass;
use Exception;

class Client extends \Hprose\Client {
    private $hdtrans;
    private $fdtrans;
    public $fullDuplex = false;
    public $readBuffer = 8192;
    public $writeBuffer = 8192;
    public $maxPoolSize = 10;
    public $noDelay = true;
    public $keepAlive = true;
    public $options = null;
    public function __construct($uris = null, $async = true) {
        parent::__construct($uris, $async);
        $this->hdtrans = new HalfDuplexTransporter($this, $async);
        $this->fdtrans = new FullDuplexTransporter($this, $async);
    }
    public function __destruct() {
        try {
            $this->loop();
        }
        catch (\Exception $e) {
        }
    }
    public function isFullDuplex() {
        return $this->fullDuplex;
    }
    public function setFullDuplex($fullDuplex) {
        $this->fullDuplex = $fullDuplex;
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
    public function getMaxPoolSize() {
        return $this->maxPoolSize;
    }
    public function setMaxPoolSize($maxPoolSize) {
        if ($maxPoolSize < 1) throw new Exception("maxPoolSize must be great than 0");
        $this->maxPoolSize = $maxPoolSize;
    }
    public function setNoDelay($value) {
        $this->noDelay = $value;
    }
    public function isNoDelay() {
        return $this->noDelay;
    }
    public function setKeepAlive($value) {
        $this->keepAlive = $value;
    }
    public function isKeepAlive() {
        return $this->keepAlive;
    }
    protected function sendAndReceive($request, stdClass $context) {
        if ($this->fullDuplex) {
            return $this->fdtrans->sendAndReceive($request, $context);
        }
        return $this->hdtrans->sendAndReceive($request, $context);
    }
    public function getOptions() {
        return $this->options;
    }
    public function setOptions(array $options) {
        $this->options = $options;
    }
    public function set($key, $value) {
        $this->options[$key] = $value;
        return $this;
    }
    public function loop() {
        if ($this->fullDuplex) {
            $this->fdtrans->loop();
        }
        else {
            $this->hdtrans->loop();
        }
    }
}
