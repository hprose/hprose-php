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
 * Hprose/HttpServer.php                                  *
 *                                                        *
 * hprose http server class for php 5.3+                  *
 *                                                        *
 * LastModified: Mar 14, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose {
    class HttpService extends Service {
        private static $errorTable = array(
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_DEPRECATED => 'Deprecated',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_USER_DEPRECATED => 'User Deprecated',
            E_STRICT => 'Runtime Notice',
            E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
        );
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
            $errorTable = self::$errorTable;

            set_error_handler(function($errno, $errstr, $errfile, $errline) use ($self, $errorTable, $context) {
                if ($self->debug) {
                    $errstr .= " in $errfile on line $errline";
                }
                $error = $errorTable[$errno] . ": " . $errstr;
                exit($self->sendError($error, $context));
            }, $this->error_types);

            ob_start(function($data) use ($self, $context) {
                $match = array();
                if (preg_match('/<b>.*? error<\/b>:(.*?)<br/', $data, $match)) {
                    if ($self->debug) {
                        $error = preg_replace('/<.*?>/', '', $match[1]);
                    }
                    else {
                        $error = preg_replace('/ in <b>.*<\/b>$/', '', $match[1]);
                    }
                    return $self->sendError(trim($error), $context);
                }
                return $self->outputFilter($data, $context);
            });
            ob_implicit_flush(0);
            @ob_clean();
            $this->sendHeader($context);
            $result = '';
            if (($_SERVER['REQUEST_METHOD'] == 'GET') && $this->get) {
                $result = $this->doFunctionList($context);
            }
            elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $result = $this->defaultHandle($request, $context);
            }
            @ob_end_clean();
            exit($result);
        }
    }

    class HttpServer extends HttpService {
        public function start() {
            $this->handle();
        }
    }
}
