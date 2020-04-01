<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Client.php                                               |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

use Exception;

class Client {
    private static $transportClasses = [];
    private static $schemes = [];
    public static function register(string $name, string $transportClass): void {
        $schemes = $transportClass::$schemes;
        foreach ($schemes as $scheme) {
            static::$schemes[$scheme] = $name;
        }
        static::$transportClasses[$name] = $transportClass;
    }
    public static function isRegister(string $name): bool {
        return isset(static::$transportClasses[$name]);
    }
    public $requestHeaders = [];
    /** @var ClientCodec $codec */
    public $codec;
    public $timeout = 30; // second
    private $urilist = [];
    public function getUris(): array{
        return $this->urilist;
    }
    public function setUris(array $uris): void {
        if (!empty($uris)) {
            $this->urilist = $uris;
            shuffle($this->urilist);
        }
    }
    private $invokeManager;
    private $ioManager;
    use PluginTrait;
    private $transports = [];
    public function __get(string $name): Transport {
        return $this->transports[$name];
    }
    public function __set(string $name, Transport $transport): void {
        $this->transports[$name] = $transport;
    }
    public function __construct(?array $urilist = null) {
        $this->codec = DefaultClientCodec::getInstance();
        $this->invokeManager = new InvokeManager([$this, 'call']);
        $this->ioManager = new IOManager([$this, 'transport']);
        foreach (static::$transportClasses as $name => $transportClass) {
            $this->transports[$name] = new $transportClass();
        }
        if (!empty($urilist)) {
            $this->urilist = $urilist;
        }
    }
    public function useService(string $namespace = ''): Proxy {
        return new Proxy($this, $namespace);
    }
    public function invoke(string $fullname, array $args = [], $context = null) {
        if ($context === null) {
            $context = new ClientContext();
        }
        if (is_array($context)) {
            $context = new ClientContext($context);
        }
        $context->init($this);
        return call_user_func_array($this->invokeManager->handler, [$fullname, &$args, $context]);
    }
    public function call(string $fullname, array $args, Context $context) {
        $request = $this->codec->encode($fullname, $args, $context);
        $response = $this->request($request, $context);
        return $this->codec->decode($response, $context);
    }
    public function request(string $request, Context $context): string {
        return call_user_func($this->ioManager->handler, $request, $context);
    }
    public function transport(string $request, Context $context): string {
        $uri = $context->uri;
        $scheme = parse_url($uri, PHP_URL_SCHEME);
        $name = static::$schemes[$scheme];
        if (isset($name)) {
            return $this->transports[$name]->transport($request, $context);
        }
        throw new Exception('The protocol ' . $scheme . ' is not supported.');
    }
    public function abort(): void {
        foreach ($this->transports as $transport) {
            $transport->abort();
        }
    }
}
