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
 * LastModified: May 26, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Http {
    class Client extends \Hprose\Client {
        private static $cookieManager = array();
        private $host = '';
        private $path = '';
        private $secure = false;
        private $proxy = '';
        private $timeout = 30000;
        private $keepAlive = true;
        private $keepAliveTimeout = 300;
        private $header;
        private $options;
        private $curl;
        private $multicurl;
        private $curl_version_lt_720;
        private $uses = array();
        private $curls = array();
        public static function keepSession() {
            if (isset($_SESSION['HPROSE_COOKIE_MANAGER'])) {
                self::$cookieManager = $_SESSION['HPROSE_COOKIE_MANAGER'];
            }
            $cookieManager = &self::$cookieManager;
            register_shutdown_function(function() use (&$cookieManager) {
                $_SESSION['HPROSE_COOKIE_MANAGER'] = $cookieManager;
            });
        }
        private function setCookie(array $headers) {
            foreach ($headers as $header) {
                @list($name, $value) = explode(':', $header, 2);
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
        private function initUrl($url) {
            if ($url) {
                $url = parse_url($url);
                $this->secure = (strtolower($url['scheme']) == 'https');
                $this->host = strtolower($url['host']);
                $this->path = isset($url['path']) ? $url['path'] : "/";
                $this->timeout = 30000;
                $this->keepAlive = true;
                $this->keepAliveTimeout = 300;
            }
        }
        public function __construct($url = '') {
            parent::__construct($url);
            $this->initUrl($url);
            $this->header = array('Content-type' => 'application/hprose');
            $this->options = array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
            );
            $this->curl = curl_init();
            $this->multicurl = curl_multi_init();
            $curl_version = curl_version();
            $this->curl_version_lt_720 = (1 == version_compare('7.20.0', $curl_version['version']));
        }
        public function __destruct() {
            try {
                $this->loop();
            }
            catch (\Exception $e) {
            }
            curl_multi_close($this->multicurl);
            curl_close($this->curl);
        }
        public function useService($url = '', $namespace = '') {
            $this->initUrl($url);
            return parent::useService($url, $namespace);
        }
        private function initCurl($curl, $request) {
            curl_setopt($curl, CURLOPT_URL, $this->url);
            curl_setopt($curl, CURLOPT_HEADER, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            if (!ini_get('safe_mode')) {
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            }
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
            $headers_array = array($this->getCookie(),
                                    "Content-Length: " . strlen($request));
            if ($this->keepAlive) {
                $headers_array[] = "Connection: keep-alive";
                $headers_array[] = "Keep-Alive: " . $this->keepAliveTimeout;
            }
            else {
                $headers_array[] = "Connection: close";
            }
            foreach ($this->header as $name => $value) {
                $headers_array[] = $name . ": " . $value;
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers_array);
            if ($this->proxy) {
                curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
            }
            if (defined('CURLOPT_TIMEOUT_MS')) {
                curl_setopt($curl, CURLOPT_TIMEOUT_MS, $this->timeout);
            }
            else {
                curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout / 1000);
            }
            foreach ($this->options as $name => $value) {
                curl_setopt($curl, $name, $value);
            }
        }
        private function getContents($response) {
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
            if ($response_code != '200') {
                throw new \Exception($response_code . ": " . $response_status . "\r\n\r\n" . $response);
            }
            $this->setCookie($http_response_header);
            return $response;
        }
        protected function sendAndReceive($request) {
            $this->initCurl($this->curl, $request);
            $data = curl_exec($this->curl);
            $errno = curl_errno($this->curl);
            if ($errno) {
                throw new \Exception($errno . ": " . curl_error($this->curl));
            }
            return $this->getContents($data);
        }
        protected function asyncSendAndReceive($request, $use) {
            $curl = curl_init();
            $this->initCurl($curl, $request);
            curl_multi_add_handle($this->multicurl, $curl);
            $this->curls[] = $curl;
            $this->uses[] = $use;
        }
        public function loop() {
            $count = count($this->curls);
            if ($count === 0) return;
            $err = null;
            try {
                $active = null;
                if ($this->curl_version_lt_720) {
                    do {
                        $status = curl_multi_exec($this->multicurl, $active);
                    } while ($status === CURLM_CALL_MULTI_PERFORM);
                }
                else {
                    $status = curl_multi_exec($this->multicurl, $active);
                }
                while ($status === CURLM_OK && $count > 0) {
                    if ($this->curl_version_lt_720) {
                        do {
                            $status = curl_multi_exec($this->multicurl, $active);
                        } while ($status === CURLM_CALL_MULTI_PERFORM);
                    }
                    else {
                        $status = curl_multi_exec($this->multicurl, $active);
                    }
                    $msgs_in_queue = null;
                    while ($info = curl_multi_info_read($this->multicurl, $msgs_in_queue)) {
                        $h = $info['handle'];
                        $index = array_search($h, $this->curls, true);
                        $use = $this->uses[$index];
                        if ($info['result'] === CURLM_OK) {
                            $data = curl_multi_getcontent($h);
                            try {
                                $response = $this->getContents($data);
                                $error = null;
                            }
                            catch (Exception $e) {
                                $response = "";
                                $error = $e;
                            }
                            $this->sendAndReceiveCallback($response, $error, $use);
                        }
                        else {
                            $this->sendAndReceiveCallback('', new \Exception($info['result'] . ": " . curl_error($h)), $use);
                        }
                        --$count;
                        if ($msgs_in_queue === 0) break;
                    }
                }
            }
            catch (\Exception $e) {
                $err = $e;
            }
            foreach($this->curls as $i => $curl) {
                curl_multi_remove_handle($this->multicurl, $curl);
                curl_close($curl);
            }
            $this->curls = array();
            $this->uses = array();
            if ($err !== null) {
                throw $err;
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
        public function setTimeout($timeout) {
            $this->timeout = $timeout;
        }
        public function getTimeout() {
            return $this->timeout;
        }
        public function setKeepAlive($keepAlive = true) {
            $this->keepAlive = $keepAlive;
        }
        public function getKeepAlive() {
            return $this->keepAlive;
        }
        public function setKeepAliveTimeout($timeout) {
            $this->keepAliveTimeout = $timeout;
        }
        public function getKeepAliveTimeout() {
            return $this->keepAliveTimeout;
        }
    }
}
