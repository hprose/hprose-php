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
 * HproseCurlHttpClient.php                               *
 *                                                        *
 * hprose curl http client class for php5.                *
 *                                                        *
 * LastModified: Jul 12, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

require_once('HproseBaseHttpClient.php');

class HproseHttpClient extends HproseBaseHttpClient {
    private $curl;
    protected function formatCookie($cookies) {
        if (count($cookies) > 0) {
            return "Cookie: " . implode('; ', $cookies);
        }
        return '';
    }
    public function __construct($url = '') {
        parent::__construct($url);
        $this->curl = curl_init();
    }
    protected function sendAndReceive($request) {
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_HEADER, TRUE);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, TRUE);
        if (!ini_get('safe_mode')) {
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, TRUE);
        }
        curl_setopt($this->curl, CURLOPT_POST, TRUE);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $request);
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
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers_array);
        if ($this->proxy) {
            curl_setopt($this->curl, CURLOPT_PROXY, $this->proxy);
        }
        if (defined(CURLOPT_TIMEOUT_MS)) {
            curl_setopt($this->curl, CURLOPT_TIMEOUT_MS, $this->timeout);
        }
        else {
            curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout / 1000);
        }
        $response = curl_exec($this->curl);
        $errno = curl_errno($this->curl);
        if ($errno) {
            throw new Exception($errno . ": " . curl_error($this->curl));
        }
        do {
            list($response_headers, $response) = explode("\r\n\r\n", $response, 2);
            $http_response_header = explode("\r\n", $response_headers);
            $http_response_firstline = array_shift($http_response_header);
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
            throw new Exception($response_code . ": " . $response_status . "\r\n\r\n" . $response);
        }
        $this->setCookie($http_response_header);
        return $response;
    }
    public function __destruct() {
        curl_close($this->curl);
    }
}

?>