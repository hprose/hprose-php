<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| HttpHandler.php                                          |
|                                                          |
| LastModified: Apr 4, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Http;

use Hprose\RPC\Core\Handler;
use Hprose\RPC\Core\Service;
use Hprose\RPC\Core\ServiceContext;

class HttpHandler implements Handler {
    public static $serverTypes = ['Hprose\\RPC\\Http\\HttpServer'];
    public $service;
    public $crossDomain = false;
    public $p3p = false;
    public $get = true;
    public $httpHeaders = [];
    private $origins = [];
    public function __construct(Service $service) {
        $this->service = $service;
    }
    public function bind($server): void {
        $server->onRequest([$this, 'handler']);
    }
    private function header($request, $response): void {
        $response->headers['Content-Type'] = 'text/plain';
        if ($this->p3p) {
            $response->headers['P3P'] =
                'CP="CAO DSP COR CUR ADM DEV TAI PSA PSD ' .
                'IVAi IVDi CONi TELo OTPi OUR DELi SAMi OTRi ' .
                'UNRi PUBi IND PHY ONL UNI PUR FIN COM NAV ' .
                'INT DEM CNT STA POL HEA PRE GOV"';
        }
        if ($this->crossDomain) {
            $origin = $request->headers['Origin'] ?? 'null';
            if ($origin !== 'null') {
                if (count($this->origins) === 0 || isset($this->origins[strtolower($origin)])) {
                    $response->headers['Access-Control-Allow-Origin'] = $origin;
                    $response->headers['Access-Control-Allow-Credentials'] = 'true';
                }
            } else {
                $response->headers['Access-Control-Allow-Origin'] = '*';
            }
        }
        if (!empty($httpHeaders)) {
            foreach ($httpHeaders as $name => $value) {
                $response->headers[$name] = $value;
            }
        }
    }
    public function handler($request, $response): void {
        $this->header($request, $response);
        $body = $request->body();
        if (strlen($body) > $this->service->maxRequestLength) {
            $response->end(413);
            return;
        }
        if ($request->method === 'GET') {
            if (!$this->get) {
                $response->end(403);
                return;
            }
        }
        $context = new ServiceContext($this->service);
        $context->remoteAddress = [
            'family' => 'tcp',
            'address' => $request->address,
            'port' => $request->port,
        ];
        $context->localAddress = [
            'family' => 'tcp',
            'address' => $request->server->address,
            'port' => $request->server->port,
        ];
        $context->handler = $this;
        $data = $this->service->handle($body, $context);
        $response->end(200, $data);
    }
}