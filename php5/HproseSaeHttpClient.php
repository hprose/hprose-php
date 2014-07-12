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
 * HproseSaeHttpClient.php                                *
 *                                                        *
 * hprose sae http client class for php5.                 *
 *                                                        *
 * LastModified: Jul 12, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

require_once('HproseBaseHttpClient.php');

class HproseHttpClient extends HproseBaseHttpClient {
    protected function formatCookie($cookies) {
        if (count($cookies) > 0) {
            return implode('; ', $cookies);
        }
        return '';
    }
    protected function sendAndReceive($request) {
        $f = new SaeFetchurl();
        $cookie = $this->getCookie();
        if ($cookie != '') {
            $f->setHeader("Cookie", $cookie);
        }
        if ($this->keepAlive) {
            $f->setHeader("Connection", "keep-alive");
            $f->setHeader("Keep-Alive", $this->keepAliveTimeout);
        }
        else {
            $f->setHeader("Connection", "close");
        }
        foreach ($this->header as $name => $value) {
            $f->setHeader($name, $value);
        }
        $f->setMethod("post");
        $f->setPostData($request);
        $f->setConnectTimeout($this->timeout);
        $f->setSendTimeout($this->timeout);
        $f->setReadTimeout($this->timeout);
        $response = $f->fetch($this->url);
        if ($f->errno()) {
            throw new Exception($f->errno() . ": " . $f->errmsg());
        }
        $http_response_header = $f->responseHeaders(false);
        $this->setCookie($http_response_header);
        return $response;
    }
}
?>