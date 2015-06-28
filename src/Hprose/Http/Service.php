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
 * Hprose/Http/Service.php                                *
 *                                                        *
 * hprose http service class for php 5.3+                 *
 *                                                        *
 * LastModified: Jun 28, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Http {
    class Service extends \Hprose\Base\Service {
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
            header("Content-Type: text/plain");
            if ($this->P3P) {
                header('P3P: CP="CAO DSP COR CUR ADM DEV TAI PSA PSD ' .
                       'IVAi IVDi CONi TELo OTPi OUR DELi SAMi OTRi ' .
                       'UNRi PUBi IND PHY ONL UNI PUR FIN COM NAV ' .
                       'INT DEM CNT STA POL HEA PRE GOV"');
            }
            if ($this->crossDomain) {
                if (isset($_SERVER['HTTP_ORIGIN']) &&
                    $_SERVER['HTTP_ORIGIN'] != "null") {
                    $origin = $_SERVER['HTTP_ORIGIN'];
                    if (count($this->origins) === 0 ||
                        isset($this->origins[strtolower($origin)])) {
                        header("Access-Control-Allow-Origin: " . $origin);
                        header("Access-Control-Allow-Credentials: true");
                    }
                }
                else {
                    header('Access-Control-Allow-Origin: *');
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
        public function handle() {
            $request = file_get_contents("php://input");
            $context = new \stdClass();
            $context->server = $this;
            $context->userdata = new \stdClass();

            $self = $this;
            $this->user_fatal_error_handler = function($error) use ($self, $context) {
                echo $self->sendError($error, $context);
            };

            $this->sendHeader($context);

            $result = '';
            if (isset($_SERVER['REQUEST_METHOD'])) {
                if (($_SERVER['REQUEST_METHOD'] == 'GET') && $this->get) {
                    $result = $this->doFunctionList($context);
                }
                elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    $result = $this->defaultHandle($request, $context);
                }
            }
            else {
                $result = $this->doFunctionList($context);
            }
            if ($result instanceof \Hprose\Future) {
                $result->then(function($result) { echo $result; });
            }
            else {
                echo $result;
            }
        }
    }
}
