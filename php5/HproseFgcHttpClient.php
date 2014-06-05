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
 * HproseFgcHttpClient.php                                *
 *                                                        *
 * hprose fgc http client class for php5.                 *
 *                                                        *
 * LastModified: Mar 19, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

require_once('HproseBaseHttpClient.php');

class HproseHttpClient extends HproseBaseHttpClient {
    protected function formatCookie($cookies) {
        if (count($cookies) > 0) {
            return "Cookie: " . implode('; ', $cookies) . "\r\n";
        }
        return '';
    }
    public function __errorHandler($errno, $errstr, $errfile, $errline) {
        throw new Exception($errstr, $errno);
    }
    protected function sendAndReceive($request) {
        $opts = array (
            'http' => array (
                'method' => 'POST',
                'header'=> $this->getCookie() .
                           "Content-Length: " . strlen($request) . "\r\n" .
                           ($this->keepAlive ?
                           "Connection: keep-alive\r\n" .
                           "Keep-Alive: " . $this->keepAliveTimeout . "\r\n" :
                           "Connection: close\r\n"),
                'content' => $request,
                'timeout' => $this->timeout / 1000.0,
            ),
        );
        foreach ($this->header as $name => $value) {
            $opts['http']['header'] .= "$name: $value\r\n";
        }
        if ($this->proxy) {
            $opts['http']['proxy'] = $this->proxy;
            $opts['http']['request_fulluri'] = true;
        }
        $context = stream_context_create($opts);
        set_error_handler(array(&$this, '__errorHandler'));
        $response = file_get_contents($this->url, false, $context);
        restore_error_handler();
        $this->setCookie($http_response_header);
        return $response;
    }
}
?>