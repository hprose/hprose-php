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
 * HproseHttpServer.php                                   *
 *                                                        *
 * hprose http server library for php.                    *
 *                                                        *
 * LastModified: Mar 29, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

require_once('HproseService.php');

class HproseHttpService extends HproseService {
    private $crossDomain = false;
    private $P3P = false;
    private $get = true;
    private $origins = array();
    private $context;
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
            if (array_key_exists('HTTP_ORIGIN', $_SERVER) &&
                $_SERVER['HTTP_ORIGIN'] != "null") {
                $origin = $_SERVER['HTTP_ORIGIN'];
                if (count($this->origins) === 0 ||
                    array_key_exists(strtolower($origin), $this->origins)) {
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
    /*
      __errorHandler and __filterHandler must be public,
      however we should never call them directly.
    */
    public function __errorHandler($errno, $errstr, $errfile, $errline) {
        if ($this->debug) {
            $errstr .= " in $errfile on line $errline";
        }
        $error = self::$errorTable[$errno] . ": " . $errstr;
        echo $this->sendError($error, $this->context);
    }
    public function __filterHandler($data) {
        $match = array();
        if (preg_match('/<b>.*? error<\/b>:(.*?)<br/', $data, $match)) {
            if ($this->debug) {
                $error = preg_replace('/<.*?>/', '', $match[1]);
            }
            else {
                $error = preg_replace('/ in <b>.*<\/b>$/', '', $match[1]);
            }
            return $this->sendError(trim($error), $this->context);
        }
    }
    public function handle() {
        $request = file_get_contents("php://input");

        $context = new stdClass();
        $context->server = $this;
        $context->userdata = new stdClass();
        $this->context = $context;

        set_error_handler(array($this, '__errorHandler'), $this->error_types);
        ob_start(array($this, '__filterHandler'));

        ob_implicit_flush(0);

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
        @ob_clean();
        @ob_end_flush();
        echo $result;
    }
}

class HproseHttpServer extends HproseHttpService {
    public function start() {
        $this->handle();
    }
}
