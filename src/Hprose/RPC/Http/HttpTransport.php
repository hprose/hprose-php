<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| HttpTransport.php                                        |
|                                                          |
| LastModified: Apr 4, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Http;

use Exception;
use Hprose\RPC\Core\Context;
use Hprose\RPC\Core\Transport;

class HttpTransport implements Transport {
    public static $schemes = ['http', 'https'];
    private $curl;
    public $httpRequestHeaders = [];
    public function __construct() {
        $this->curl = curl_init();
        $this->setKeepAlive(300);
    }
    public function __destruct() {
        curl_close($this->curl);
    }
    public function setKeepAlive(int $timeout, int $max = 0): void {
        if ($timeout > 0) {
            $this->httpRequestHeaders['Connection'] = 'keep-alive';
            $this->httpRequestHeaders['Keep-Alive'] = 'timeout=' . $timeout;
            if ($max > 0) {
                $this->httpRequestHeaders['Keep-Alive'] .= ', max=' . $max;
            }
            curl_setopt($this->curl, CURLOPT_FRESH_CONNECT, false);
            curl_setopt($this->curl, CURLOPT_FORBID_REUSE, false);
        } else {
            $this->httpRequestHeaders['Connection'] = 'close';
            unset($this->httpRequestHeaders['Keep-Alive']);
            curl_setopt($this->curl, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($this->curl, CURLOPT_FORBID_REUSE, true);
        }
    }
    public function setOptions(array $options): void {
        curl_setopt_array($this->curl, $options);
    }
    public function transport(string $request, Context $context): string {
        $timeout = $context->timeout;
        if ($timeout > 0) {
            curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
        } else {
            curl_setopt($this->curl, CURLOPT_TIMEOUT, 0);
        }
        curl_setopt($this->curl, CURLOPT_URL, $context->uri);
        $headers = [];
        foreach ($this->httpRequestHeaders as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }
        if (is_array($context->httpRequestHeaders)) {
            foreach ($context->httpRequestHeaders as $name => $value) {
                $headers[] = $name . ': ' . $value;
            }
        }
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        if (!ini_get('safe_mode')) {
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
        }
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $request);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HEADER, true);
        $response = curl_exec($this->curl);
        $errno = curl_errno($this->curl);
        if ($errno) {
            throw new Exception($errno . ': ' . curl_error($this->curl));
        }
        do {
            [$header, $response] = explode("\r\n\r\n", $response, 2);
            $headers = explode("\r\n", $header);
            $firstline = array_shift($headers);
            $matches = [];
            if (preg_match('@^HTTP/[0-9]\.[0-9]\s([0-9]{3})\s(.*)@', $firstline, $matches)) {
                $code = $matches[1];
                $status = trim($matches[2]);
            } else {
                $code = "500";
                $status = "Internal Server Error";
            }
        } while (in_array(substr($code, 0, 1), ["1", "3"]));
        $context->httpStatusCode = $code;
        $context->httpStatusText = $status;
        $httpResponseHeaders = [];
        foreach ($headers as $headerline) {
            $pair = explode(':', $headerline, 2);
            $name = trim($pair[0]);
            $value = (count($pair) > 1) ? trim($pair[1]) : '';
            if (array_key_exists($name, $httpResponseHeaders)) {
                if (is_array($httpResponseHeaders[$name])) {
                    $httpResponseHeaders[$name][] = $value;
                } else {
                    $httpResponseHeaders[$name] = [$httpResponseHeaders[$name], $value];
                }
            } else {
                $httpResponseHeaders[$name] = $value;
            }
        }
        $context->httpResponseHeaders = $httpResponseHeaders;
        if ($code != '200') {
            throw new Exception($code . ": " . $status . "\r\n\r\n" . $response);
        }
        return $response;
    }
    public function abort(): void {
        curl_close($this->curl);
        $this->curl = curl_init();
        $this->setKeepAlive(300);
    }
}
