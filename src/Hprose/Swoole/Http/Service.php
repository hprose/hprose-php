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
 * LastModified: Jun 28, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Http {
    class Service extends \Hprose\Base\Service {
        const MAX_PACK_LEN = 0x200000;
        private $crossDomain = false;
        private $P3P = false;
        private $get = true;
        private $origins = array();
        public $onSendHeader = null;

        private function sendHeader($context) {
            if ($this->onSendHeader !== null) {
                $sendHeader = $this->onSendHeader;
                call_user_func($sendHeader, $context);
            }
            $request = $context->request;
            $response = $context->response;
            $response->header('Content-Type', 'text/plain');
            if ($this->P3P) {
                $response->header('P3P',
                       'CP="CAO DSP COR CUR ADM DEV TAI PSA PSD ' .
                       'IVAi IVDi CONi TELo OTPi OUR DELi SAMi OTRi ' .
                       'UNRi PUBi IND PHY ONL UNI PUR FIN COM NAV ' .
                       'INT DEM CNT STA POL HEA PRE GOV"');
            }
            if ($this->crossDomain) {
                if (array_key_exists('http_origin', $request->header) &&
                    $request->header['http_origin'] != "null") {
                    $origin = $request->header['http_origin'];
                    if (count($this->origins) === 0 ||
                        array_key_exists(strtolower($origin), $this->origins)) {
                        $response->header('Access-Control-Allow-Origin', $origin);
                        $response->header('Access-Control-Allow-Credentials',
                                         'true');
                    }
                }
                else {
                    $response->header('Access-Control-Allow-Origin', '*');
                }
            }
        }
        private function send($data, $response) {
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
        public function isCrossDomainEnabled() {
            return $this->crossDomain;
        }
        public function setCrossDomainEnabled($enable = true) {
            $this->crossDomain = $enable;
        }
        public function isP3PEnabled() {
            return $this->P3P;
        }
        public function setP3PEnabled($enable = true) {
            $this->P3P = $enable;
        }
        public function isGetEnabled() {
            return $this->get;
        }
        public function setGetEnabled($enable = true) {
            $this->get = $enable;
        }
        public function addAccessControlAllowOrigin($origin) {
            $count = count($origin);
            if (($count > 0) && ($origin[$count - 1] === "/")) {
                $origin = substr($origin, 0, -1);
            }
            $this->origins[strtolower($origin)] = true;
        }
        public function removeAccessControlAllowOrigin($origin) {
            $count = count($origin);
            if (($count > 0) && ($origin[$count - 1] === "/")) {
                $origin = substr($origin, 0, -1);
            }
            unset($this->origins[strtolower($origin)]);
        }
        public function handle($request, $response) {
            $data = $request->rawContent();

            $context = new \stdClass();
            $context->server = $this;
            $context->request = $request;
            $context->response = $response;
            $context->userdata = new \stdClass();

            $self = $this;
            $this->user_fatal_error_handler = function($error) use ($self, $context) {
                $self->send($self->sendError($error, $context), $context->response);
            };

            $this->sendHeader($context);
            $result = '';
            if (($request->server['request_method'] == 'GET') && $this->get) {
                $result = $this->doFunctionList($context);
            }
            elseif ($request->server['request_method'] == 'POST') {
                $result = $this->defaultHandle($data, $context);
            }
            if ($result instanceof \Hprose\Future) {
                $result->then(function($result) use ($self, $response) {
                    $self->send($result, $response);
                });
            }
            else {
                $this->send($result, $response);
            }
        }
    }
}
