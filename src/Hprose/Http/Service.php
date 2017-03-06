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
 * LastModified: Sep 9, 2016                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Http;

use stdClass;
use Hprose\Future;

class Service extends \Hprose\Service {
    const ORIGIN = 'HTTP_ORIGIN';
    public $onSendHeader = null;
    public $crossDomain = false;
    public $p3p = false;
    public $get = true;
    private $origins = array();

    public function header($name, $value, $context) {
        header("$name: $value");
    }
    public function getAttribute($name, $context) {
        return $_SERVER[$name];
    }
    public function hasAttribute($name, $context) {
        return isset($_SERVER[$name]);
    }
    protected function readRequest($context) {
        return file_get_contents("php://input");
    }
    protected function createContext($request, $response) {
        $context = new stdClass();
        $context->server = $this;
        $context->request = $request;
        $context->response = $response;
        $context->userdata = new stdClass();
        return $context;
    }
    public function writeResponse($data, $context) {
        echo $data;
    }
    public function isGet($context) {
        return isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] === 'GET');
    }
    public function isPost($context) {
        return isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] === 'POST');
    }

    private function sendHeader($context) {
        if ($this->onSendHeader !== null) {
            $sendHeader = $this->onSendHeader;
            call_user_func($sendHeader, $context);
        }
        $this->header('Content-Type', 'text/plain', $context);
        if ($this->p3p) {
            $this->header('P3P', 'CP="CAO DSP COR CUR ADM DEV TAI PSA PSD ' .
                   'IVAi IVDi CONi TELo OTPi OUR DELi SAMi OTRi ' .
                   'UNRi PUBi IND PHY ONL UNI PUR FIN COM NAV ' .
                   'INT DEM CNT STA POL HEA PRE GOV"', $context);
        }
        if ($this->crossDomain) {
            if ($this->hasAttribute(static::ORIGIN, $context) &&
                $this->getAttribute(static::ORIGIN, $context) != "null") {
                $origin = $this->getAttribute(static::ORIGIN, $context);
                if (count($this->origins) === 0 ||
                    isset($this->origins[strtolower($origin)])) {
                    $this->header('Access-Control-Allow-Origin', $origin, $context);
                    $this->header('Access-Control-Allow-Credentials', 'true', $context);
                }
            }
            else {
                $this->header('Access-Control-Allow-Origin', '*', $context);
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
        return $this->p3p;
    }
    public function setP3PEnabled($enable = true) {
        $this->p3p = $enable;
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
    public function handle($request = null, $response = null) {
        $context = $this->createContext($request, $response);
        $self = $this;
        $this->userFatalErrorHandler = function($error) use ($self, $context) {
            $self->writeResponse($self->endError($error, $context), $context);
        };

        $this->sendHeader($context);

        $result = '';
        if ($this->isGet($context)) {
            if ($this->get) {
                $result = $this->doFunctionList();
            }
        }
        elseif ($this->isPost($context)) {
            $result = $this->defaultHandle($this->readRequest($context), $context);
        }
        else {
            $result = $this->doFunctionList();
        }
        if (Future\isFuture($result)) {
            $result->then(function($result) use ($self, $context) {
                $self->header('Content-Length', strlen($result), $context);
                $self->writeResponse($result, $context);
            });
        }
        else {
            $this->header('Content-Length', strlen($result), $context);
            $this->writeResponse($result, $context);
       }
       return $context->response;
    }
}
