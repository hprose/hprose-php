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
 * Hprose/Http/Client.php                                 *
 *                                                        *
 * hprose http client class for php 5.3+                  *
 *                                                        *
 * LastModified: Dec 5, 2016                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Http;

use stdClass;
use Exception;
use Throwable;
use Hprose\Future;

class Client extends \Hprose\Client {
    private static $cookieManager = array();
    private $host = '';
    private $path = '';
    private $secure = false;
    public $proxy = '';
    public $keepAlive = true;
    public $keepAliveTimeout = 300;
    private $header;
    private $options;
    private $curl;
    private $curlVersionLittleThan720;
    private $results = array();
    private $curls = array();
    private $contexts = array();
    public static function keepSession() {
        if (isset($_SESSION['HPROSE_COOKIE_MANAGER'])) {
            self::$cookieManager = $_SESSION['HPROSE_COOKIE_MANAGER'];
        }
        $cookieManager = &self::$cookieManager;
        register_shutdown_function(function() use (&$cookieManager) {
            $_SESSION['HPROSE_COOKIE_MANAGER'] = $cookieManager;
        });
    }
    public function __construct($uris = null, $async = true) {
        parent::__construct($uris, $async);
        $this->header = array('Content-type' => 'application/hprose');
        $this->options = array(
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_NOSIGNAL => 1
        );
        $curl_version = curl_version();
        $this->curlVersionLittleThan720 = (1 == version_compare('7.20.0', $curl_version['version']));
    }
    public function __destruct() {
        if ($this->async) {
            try {
                $this->loop();
            }
            catch (Exception $e) {
            }
        }
    }
    public function setHeader($name, $value) {
        $lname = strtolower($name);
        if ($lname != 'content-type' &&
            $lname != 'content-length' &&
            $lname != 'host') {
            if ($value) {
                $this->header[$name] = $value;
            }
            else {
                unset($this->header[$name]);
            }
        }
    }
    public function setOption($name, $value) {
        $this->options[$name] = $value;
    }
    public function removeOption($name) {
        unset($this->options[$name]);
    }
    public function setProxy($proxy = '') {
        $this->proxy = $proxy;
    }
    public function setKeepAlive($keepAlive = true) {
        $this->keepAlive = $keepAlive;
    }
    public function isKeepAlive() {
        return $this->keepAlive;
    }
    public function setKeepAliveTimeout($timeout) {
        $this->keepAliveTimeout = $timeout;
    }
    public function getKeepAliveTimeout() {
        return $this->keepAliveTimeout;
    }
    private function setCookie(array $headers) {
        foreach ($headers as $header) {
            $pair = explode(':', $header, 2);
            $name = $pair[0];
            $value = (count($pair) > 1) ? $pair[1] : '';
            if (strtolower($name) == 'set-cookie' ||
                strtolower($name) == 'set-cookie2') {
                $cookies = explode(';', trim($value));
                $cookie = array();
                $pair = explode('=', trim($cookies[0]), 2);
                $cookie['name'] = $pair[0];
                if (count($pair) > 1) $cookie['value'] = $pair[1];
                for ($i = 1; $i < count($cookies); $i++) {
                    $pair = explode('=', trim($cookies[$i]), 2);
                    $cookie[strtoupper($pair[0])] = (count($pair) > 1) ? $pair[1] : '';
                }
                // Tomcat can return SetCookie2 with path wrapped in "
                if (isset($cookie['PATH'])) {
                    $cookie['PATH'] = trim($cookie['PATH'], '"');
                }
                else {
                    $cookie['PATH'] = '/';
                }
                if (isset($cookie['DOMAIN'])) {
                    $cookie['DOMAIN'] = strtolower($cookie['DOMAIN']);
                }
                else {
                    $cookie['DOMAIN'] = $this->host;
                }
                if (!isset(self::$cookieManager[$cookie['DOMAIN']])) {
                    self::$cookieManager[$cookie['DOMAIN']] = array();
                }
                self::$cookieManager[$cookie['DOMAIN']][$cookie['name']] = $cookie;
            }
        }
    }
    private function getCookie() {
        $cookies = array();
        foreach (self::$cookieManager as $domain => $cookieList) {
            if (strpos($this->host, $domain) !== false) {
                $names = array();
                foreach ($cookieList as $cookie) {
                    if (isset($cookie['EXPIRES']) && (time() > strtotime($cookie['EXPIRES']))) {
                        $names[] = $cookie['name'];
                    }
                    elseif (strpos($this->path, $cookie['PATH']) === 0) {
                        if ((($this->secure &&
                             isset($cookie['SECURE'])) ||
                             !isset($cookie['SECURE'])) &&
                              isset($cookie['value'])) {
                            $cookies[] = $cookie['name'] . '=' . $cookie['value'];
                        }
                    }
                }
                foreach ($names as $name) {
                    unset(self::$cookieManager[$domain][$name]);
                }
            }
        }
        if (count($cookies) > 0) {
            return "Cookie: " . implode('; ', $cookies);
        }
        return '';
    }
    protected function setUri($uri) {
        parent::setUri($uri);
        $url = parse_url($uri);
        $this->secure = (strtolower($url['scheme']) == 'https');
        $this->host = strtolower($url['host']);
        $this->path = isset($url['path']) ? $url['path'] : "/";
        $this->keepAlive = true;
        $this->keepAliveTimeout = 300;
    }
    private function initCurl($curl, $request, $context) {
        $timeout = $context->timeout;
        foreach ($this->options as $name => $value) {
            curl_setopt($curl, $name, $value);
        }
        curl_setopt($curl, CURLOPT_URL, $this->uri);
        if (!ini_get('safe_mode')) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
        $headers_array = array($this->getCookie(),
                                "Content-Length: " . strlen($request));
        if ($this->keepAlive) {
            $headers_array[] = "Connection: keep-alive";
            $headers_array[] = "Keep-Alive: " . $this->keepAliveTimeout;
            curl_setopt($curl, CURLOPT_FRESH_CONNECT, false);
            curl_setopt($curl, CURLOPT_FORBID_REUSE, false);
        }
        else {
            $headers_array[] = "Connection: close";
        }
        foreach ($this->header as $name => $value) {
            $headers_array[] = $name . ": " . $value;
        }
        if (isset($context->httpHeader)) {
            $header = $context->httpHeader;
            foreach ($header as $name => $value) {
                $headers_array[] = $name . ": " .
                    (is_array($value) ? join(", ", $value) : $value);
            }
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers_array);
        if ($this->proxy) {
            curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
        }
        if (defined('CURLOPT_TIMEOUT_MS')) {
            curl_setopt($curl, CURLOPT_TIMEOUT_MS, $timeout);
        }
        else {
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout / 1000);
        }
    }
    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function getContents($response, $context) {
        do {
            list($response_headers, $response) = explode("\r\n\r\n", $response, 2);
            $http_response_header = explode("\r\n", $response_headers);
            $http_response_firstline = array_shift($http_response_header);
            $matches = array();
            if (preg_match('@^HTTP/[0-9]\.[0-9]\s([0-9]{3})\s(.*)@',
                           $http_response_firstline, $matches)) {
                $response_code = $matches[1];
                $response_status = trim($matches[2]);
            }
            else {
                $response_code = "500";
                $response_status = "Unknown Error.";
            }
        } while (substr($response_code, 0, 1) == "1");
        $header = array();
        foreach ($http_response_header as $headerline) {
            $pair = explode(':', $headerline, 2);
            $name = trim($pair[0]);
            $value = (count($pair) > 1) ? trim($pair[1]) : '';
            if (array_key_exists($name, $header)) {
                if (is_array($header[$name])) {
                    $header[$name][] = $value;
                }
                else {
                    $header[$name] = array($header[$name], $value);
                }
            }
            else {
                $header[$name] = $value;
            }
        }
        $context->httpHeader = $header;
        if ($response_code != '200') {
            throw new Exception($response_code . ": " . $response_status . "\r\n\r\n" . $response);
        }
        $this->setCookie($http_response_header);
        return $response;
    }
    private function syncSendAndReceive($request, stdClass $context) {
        $curl = curl_init();
        $this->initCurl($curl, $request, $context);
        $data = curl_exec($curl);
        $errno = curl_errno($curl);
        if ($errno) {
            throw new Exception($errno . ": " . curl_error($curl));
        }
        $data = $this->getContents($data, $context);
        curl_close($curl);
        return $data;
    }
    private function asyncSendAndReceive($request, stdClass $context) {
        $result = new Future();
        $curl = curl_init();
        $this->initCurl($curl, $request, $context);
        $this->curls[] = $curl;
        $this->results[] = $result;
        $this->contexts[] = $context;
        return $result;
    }
    protected function sendAndReceive($request, stdClass $context) {
        if ($this->async) {
            return $this->asyncSendAndReceive($request, $context);
        }
        return $this->syncSendAndReceive($request, $context);
    }
    private function curlMultiExec($multicurl, &$active) {
        if ($this->curlVersionLittleThan720) {
            do {
                $status = curl_multi_exec($multicurl, $active);
            } while ($status === CURLM_CALL_MULTI_PERFORM);
            return $status;
        }
        return curl_multi_exec($multicurl, $active);
    }
    public function loop() {
        $self = $this;
        $multicurl = curl_multi_init();
        while (($count = count($this->curls)) > 0) {
            $curls = $this->curls;
            $this->curls = array();
            $results = $this->results;
            $this->results = array();
            $contexts = $this->contexts;
            $this->contexts = array();
            foreach ($curls as $curl) {
                curl_multi_add_handle($multicurl, $curl);
            }
            $err = null;
            try {
                $active = null;
                $status = $this->curlMultiExec($multicurl, $active);
                while ($status === CURLM_OK && $count > 0) {
                    $status = $this->curlMultiExec($multicurl, $active);
                    $msgs_in_queue = null;
                    while ($info = curl_multi_info_read($multicurl, $msgs_in_queue)) {
                        $handle = $info['handle'];
                        $index = array_search($handle, $curls, true);
                        $context = $contexts[$index];
                        $results[$index]->resolve(Future\sync(function() use ($self, $info, $handle, $context) {
                            if ($info['result'] === CURLM_OK) {
                                return $self->getContents(curl_multi_getcontent($handle), $context);
                            }
                            throw new Exception($info['result'] . ": " . curl_error($handle));
                        }));
                        --$count;
                        if ($msgs_in_queue === 0) break;
                    }

                    // See https://bugs.php.net/bug.php?id=61141
                    if (curl_multi_select($multicurl) === -1) {
                        usleep(100);
                    }
                }
            }
            catch (Exception $e) {
                $err = $e;
            }
            catch (Throwable $e) {
                $err = $e;
            }
            foreach($curls as $index => $curl) {
                curl_multi_remove_handle($multicurl, $curl);
                curl_close($curl);
                if ($err !== null) $results[$index]->reject($err);
            }
        }
        curl_multi_close($multicurl);
    }
}
