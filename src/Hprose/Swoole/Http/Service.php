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
 * Hprose/Swoole/Http/Service.php                         *
 *                                                        *
 * hprose swoole http service library for php 5.3+        *
 *                                                        *
 * LastModified: Jul 18, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Http;

class Service extends \Hprose\Http\Service {
    const ORIGIN = 'origin';
    const MAX_PACK_LEN = 0x200000;
    public $settings = array();
    public function set($settings) {
        $this->settings = array_replace($this->settings, $settings);
    }
    protected function header($name, $value, $context) {
        $context->response->header($name, $value);
    }
    protected function getAttribute($name, $context) {
        return $context->request->header[$name];
    }
    protected function hasAttribute($name, $context) {
        return array_key_exists($name, $context->request->header);
    }
    protected function readRequest($context) {
        return $context->request->rawContent();
    }
    protected function createContext($request = null, $response = null) {
        $context = parent::createContext();
        $context->server = $this;
        $context->request = $request;
        $context->response = $response;
        return $context;
    }
    protected function writeResponse($data, $context) {
        $response = $context->response;
        $len = strlen($data);
        if ($len <= self::MAX_PACK_LEN) {
            $response->end($data);
        }
        else {
            for ($i = 0; $i < $len; $i += self::MAX_PACK_LEN) {
                if (!$response->write(substr($data, $i, min($len - $i, self::MAX_PACK_LEN)))) {
                    return false;
                }
            }
            $response->end();
        }
        return true;
    }
    protected function isGet($context) {
        return $context->request->server['request_method'] == 'GET';
    }
    protected function isPost($context) {
        return $context->request->server['request_method'] == 'POST';
    }
    public function httpHandle($server) {
        $server->on('request', array($this, 'handle'));
    }
}
