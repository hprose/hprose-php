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
 * Hprose/Swoole/HttpService.php                          *
 *                                                        *
 * hprose swoole http service library for php 5.3+        *
 *                                                        *
 * LastModified: Apr 17, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Http {
    class Service extends \Hprose\Service {
        private $crossDomain = false;
        private $P3P = false;
        private $get = true;
        private $origins = array();
        public $onSendHeader = null;

        private function sendHeader($context) {
            if ($this->onSendHeader !== null) {
                $sendHeader = $this->onSendHeader;
                $sendHeader($context);
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

            set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($self, $context) {
                if ($self->debug) {
                    $errstr .= " in $errfile on line $errline";
                }
                $error = $self->getErrorTypeString($errno) . ": " . $errstr;
                $context->response->end($self->sendError($error, $context));
            }, $this->error_types);
            ob_start(function ($data) use ($self, $context) {
                $match = array();
                if (preg_match('/<b>.*? error<\/b>:(.*?)<br/', $data, $match)) {
                    if ($self->debug) {
                        $error = preg_replace('/<.*?>/', '', $match[1]);
                    }
                    else {
                        $error = preg_replace('/ in <b>.*<\/b>$/', '', $match[1]);
                    }
                    $data = $self->sendError(trim($error), $context);
                    $context->response->end($data);
                }
            });
            ob_implicit_flush(0);

            $this->sendHeader($context);
            $result = '';
            if (($request->server['request_method'] == 'GET') && $this->get) {
                $result = $this->doFunctionList($context);
            }
            elseif ($request->server['request_method'] == 'POST') {
                $result = $this->defaultHandle($data, $context);
            }
            ob_clean();
            ob_end_flush();
            restore_error_handler();
            $response->end($result);
        }
    }

    class Server extends Service {
        private $http;
        public function __construct($host, $port) {
            $this->http = new \swoole_http_server($host, $port);
        }
        public function set($setting) {
            $this->http->set($setting);
        }
        public function addListener($host, $port) {
            $this->http->addListener($host, $port);
        }
        public function start() {
            $this->http->on('request', array($this, 'handle'));
            $this->http->start();
        }
    }
}
