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
 * HproseBaseHttpClient.php                               *
 *                                                        *
 * hprose base http client class for php5.                *
 *                                                        *
 * LastModified: Jul 11, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

require_once('HproseCommon.php');
require_once('HproseIO.php');
require_once('HproseClient.php');

abstract class HproseBaseHttpClient extends HproseClient {
    protected $host;
    protected $path;
    protected $secure;
    protected $proxy;
    protected $header;
    protected $timeout;
    protected $keepAlive;
    protected $keepAliveTimeout;
    protected static $cookieManager = array();
    static function hproseKeepCookieInSession() {
        $_SESSION['HPROSE_COOKIE_MANAGER'] = self::$cookieManager;
    }
    public static function keepSession() {
        if (array_key_exists('HPROSE_COOKIE_MANAGER', $_SESSION)) {
            self::$cookieManager = $_SESSION['HPROSE_COOKIE_MANAGER'];
        }
        register_shutdown_function(array('HproseBaseHttpClient', 'hproseKeepCookieInSession'));
    }
    protected function setCookie($headers) {
        foreach ($headers as $header) {
            @list($name, $value) = explode(':', $header, 2);
            if (strtolower($name) == 'set-cookie' ||
                strtolower($name) == 'set-cookie2') {
                $cookies = explode(';', trim($value));
                $cookie = array();
                $pair = explode('=', trim($cookies[0]), 2);
                $cookie['name'] = $pair[0];
                $cookie['value'] = (count($pair) > 1) ? $pair[1] : '';
                for ($i = 1; $i < count($cookies); $i++) {
                    $pair = explode('=', trim($cookies[$i]), 2);
                    $cookie[strtoupper($pair[0])] = (count($pair) > 1) ? $pair[1] : '';
                }
                // Tomcat can return SetCookie2 with path wrapped in "
                if (array_key_exists('PATH', $cookie)) {
                    $cookie['PATH'] = trim($cookie['PATH'], '"');
                }
                else {
                    $cookie['PATH'] = '/';
                }
                if (array_key_exists('EXPIRES', $cookie)) {
                    $cookie['EXPIRES'] = strtotime($cookie['EXPIRES']);
                }
                if (array_key_exists('DOMAIN', $cookie)) {
                    $cookie['DOMAIN'] = strtolower($cookie['DOMAIN']);
                }
                else {
                    $cookie['DOMAIN'] = $this->host;
                }
                $cookie['SECURE'] = array_key_exists('SECURE', $cookie);
                if (!array_key_exists($cookie['DOMAIN'], self::$cookieManager)) {
                    self::$cookieManager[$cookie['DOMAIN']] = array();
                }
                self::$cookieManager[$cookie['DOMAIN']][$cookie['name']] = $cookie;
            }
        }
    }
    protected abstract function formatCookie($cookies);
    protected function getCookie() {
        $cookies = array();
        foreach (self::$cookieManager as $domain => $cookieList) {
            if (strpos($this->host, $domain) !== false) {
                $names = array();
                foreach ($cookieList as $cookie) {
                    if (array_key_exists('EXPIRES', $cookie) && (time() > $cookie['EXPIRES'])) {
                        $names[] = $cookie['name'];
                    }
                    elseif (strpos($this->path, $cookie['PATH']) === 0) {
                        if ((($this->secure && $cookie['SECURE']) ||
                             !$cookie['SECURE']) && !is_null($cookie['value'])) {
                            $cookies[] = $cookie['name'] . '=' . $cookie['value'];
                        }
                    }
                }
                foreach ($names as $name) {
                    unset(self::$cookieManager[$domain][$name]);
                }
            }
        }
        return $this->formatCookie($cookies);
    }
    public function __construct($url = '') {
        parent::__construct($url);
        $this->header = array('Content-type' => 'application/hprose');
    }
    public function useService($url = '', $namespace = '') {
        $serviceProxy = parent::useService($url, $namespace);
        if ($url) {
            $url = parse_url($url);
            $this->secure = (strtolower($url['scheme']) == 'https');
            $this->host = strtolower($url['host']);
            $this->path = $url['path'];
            $this->timeout = 30000;
            $this->keepAlive = true;
            $this->keepAliveTimeout = 300;
        }
        return $serviceProxy;
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
    public function setProxy($proxy = NULL) {
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

?>