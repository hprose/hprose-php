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
 * Hprose/Client.php                                      *
 *                                                        *
 * hprose client class for php 5.3+                       *
 *                                                        *
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

use stdClass;
use Closure;
use Exception;
use Throwable;
use ReflectionMethod;
use ReflectionFunction;

abstract class Client extends HandlerManager {
    private $index = -1;
    protected $async = true;
    public $uri = null;
    public $uris = null;
    public $filters = array();
    public $timeout = 30000;
    public $retry = 10;
    public $idempontent = false;
    public $failswitch = false;
    public $byref = false;
    public $simple = false;

    private static $clientFactories = array();
    private static $clientFactoriesInited = false;

    public static function registerClientFactory($scheme, $clientFactory) {
        self::$clientFactories[$scheme] = $clientFactory;
    }

    public static function tryRegisterClientFactory($scheme, $clientFactory) {
        if (empty(self::$clientFactories[$scheme])) {
            self::$clientFactories[$scheme] = $clientFactory;
        }
    }

    private static function initClientFactories() {
        // TODO TODO TODO TODO TODO TODO TODO TODO
        // TODO TODO TODO TODO TODO TODO TODO TODO
        // TODO TODO TODO TODO TODO TODO TODO TODO
        // TODO TODO TODO TODO TODO TODO TODO TODO
        // TODO TODO TODO TODO TODO TODO TODO TODO
        // if (php_sapi_name() == "cli" && extension_loaded("swoole")) {
        //     tryRegisterClientFactory("http", "\\Hprose\\Swoole\\Http\\Client");
        // }
        // else {
        tryRegisterClientFactory("http", "\\Hprose\\Http\\Client");
        tryRegisterClientFactory("tcp", "\\Hprose\\Socket\\Client");
        tryRegisterClientFactory("ssl", "\\Hprose\\Socket\\Client");
        tryRegisterClientFactory("sslv2", "\\Hprose\\Socket\\Client");
        tryRegisterClientFactory("sslv3", "\\Hprose\\Socket\\Client");
        tryRegisterClientFactory("tls", "\\Hprose\\Socket\\Client");
        tryRegisterClientFactory("unix", "\\Hprose\\Socket\\Client");
        // }
        self::$clientFactoriesInited = true;
    }

    public static function create($uris, $async = true) {
        if (!self::$clientFactoriesInited) self::initClientFactories();
        if (is_string($uris)) $uris = array($uris); 
        $scheme = strtolower(parse_url($uris[0], PHP_URL_SCHEME));
        $n = count($uris);
        for ($i = 1; $i < $n; ++$i) {
            if (strtolower(parse_url($uris[$i], PHP_URL_SCHEME)) != $scheme) {
                throw new Exception("Not support multiple protocol.");
            }
        }
        $clientFactory = self::$clientFactories[$scheme];
        if (empty($clientFactory)) {
            throw new Exception("This client doesn't support $scheme scheme.");
        }
        return new $clientFactory($uris, $async);
    }

    public function __construct($uris = null, $async = true) {
        parent::__construct();
        if ($uris != null) {
            if (is_string($uris)) $uris = array($uris); 
            if (is_array($uris)) {
                $this->useService($uris);
            }
            if (is_bool($uris)) {
                $async = $uris;
            } 
        }
        $this->async = $async;
    }

    public function close() {}

    public final function getTimeout() {
        return $this->timeout;
    }

    public final function setTimeout($timeout) {
        if ($timeout < 1) throw new Exception("timeout must be great than 0");
        $this->timeout = $timeout;
    }

    public final function getRetry() {
        return $this->retry;
    }

    public final function setRetry($retry) {
        $this->retry = $retry;
    }

    public final function isIdempontent() {
        return $this->idempontent;
    }

    public final function setIdempontent($idempontent) {
        $this->idempontent = $idempontent;
    }

    public final function isFailswitch() {
        return $this->failswitch;
    }

    public final function setFailswitch($failswitch) {
        $this->failswitch = $failswitch;
    }

    public final function isByref() {
        return $this->byref;
    }

    public final function setByref($byref) {
        $this->byref = $byref;
    }

    public final function isSimple() {
        return $this->simple;
    }
    public final function setSimple($simple = true) {
        $this->simple = $simple;
    }

    public final function getFilter() {
        if (empty($this->filters)) {
            return null;
        }
        return $this->filters[0];
    }

    public final function setFilter(Filter $filter) {
        $this->filters = array();
        if ($filter !== null) {
            $this->filters[] = $filter;
        }
    }

    public final function addFilter(Filter $filter) {
        if ($filter !== null) {
            if (empty($this->filters)) {
                $this->filters = array($filter);
            }
            else {
                $this->filters[] = $filter;
            }
        }
    }

    public final function removeFilter(Filter $filter) {
        if (empty($this->filters)) {
            return false;
        }
        $i = array_search($filter, $this->filters);
        if ($i === false || $i === null) {
            return false;
        }
        $this->filters = array_splice($this->filters, $i, 1);
        return true;
    }

    protected function setUri($uri) {
        $this->uri = $uri;
    }

    public function useService($uris = array(), $namespace = '') {
        if (!empty($uris)) {
            if (is_string($uris)) {
                $this->uris = array($uris);
                $this->index = 0;
                $this->setUri($uris);
            }
            else {
                $this->uris = $uris;
                $this->index = mt_rand(0, count($uris) - 1);
                $this->setUri($uris[$this->index]);
            }
        }
        if ($namespace) {
            $namespace .= "_";
        }
        return new Proxy($this, $namespace);
    }

    private function outputFilter($request, stdClass $context) {
        if (empty($this->filters)) return $request;
        $count = count($this->filters);
        for ($i = 0; $i < $count; ++$i) {
            $request = $this->filters[$i]->outputFilter($request, $context);
        }
        return $request;
    }

    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function inputFilter($response, stdClass $context) {
        if (empty($this->filters)) return $response;
        $count = count($this->filters);
        for ($i = $count - 1; $i >= 0; --$i) {
            $response = $this->filters[$i]->inputFilter($response, $context);
        }
        return $response;
    }

    protected function wait($interval, $callback) {
        $seconds = floor($interval);
        $nanoseconds = ($interval - $seconds) * 1000000000;
        time_nanosleep($seconds, $nanoseconds);
        return $callback();
    }

    private function failswitch() {
        $i = $this->index + 1;
        if ($i >= count($this->uris)) {
            $i = 0;
        }
        $this->index = $i;
        $this->setUri($this->uris[$i]);
    }

    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function retry($request, stdClass $context) {
        if ($context->failswitch) {
            $this->failswitch();
        }
        if ($context->idempontent) {
            $n = $context->retry;
            if ($n > 0) {
                $context->retry = $n - 1;
                $interval = ($n >= 10) ? 0.5 : (10 - $n) * 0.5;
                $self = $this;
                return $this->wait($interval, function() use ($self, $request, $context) {
                    return $self->sendRequest($request, $context);
                });
            }
        }
        return null;
    }

    private function encode($name, array $args, stdClass $context) {
        $stream = new BytesIO(Tags::TagCall);
        $writer = new Writer($stream, $context->simple);
        $writer->writeString($name);
        if (count($args) > 0 || $context->byref) {
            $writer->reset();
            $writer->writeArray($args);
            if ($context->byref) {
                $writer->writeBoolean(true);
            }
        }
        $stream->write(Tags::TagEnd);
        $request = $stream->toString();
        $stream->close();
        return $request;
    }

    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function decode($response, array &$args, stdClass $context) {
        if ($context->oneway) return null;
        if (empty($response)) throw new Exception("EOF");
        if ($response[strlen($response) - 1] !== Tags::TagEnd) {
            throw new Exception("Wrong Response: \r\n$response");
        }
        $mode = $context->mode;
        if ($mode === ResultMode::RawWithEndTag) {
            return $response;
        }
        elseif ($mode === ResultMode::Raw) {
            return substr($response, 0, -1);
        }
        $stream = new BytesIO($response);
        $reader = new Reader($stream);
        $result = null;
        $tag = $stream->getc();
        if ($tag === Tags::TagResult) {
            if ($mode === ResultMode::Normal) {
                $result = $reader->unserialize();
            }
            elseif ($mode === ResultMode::Serialized) {
                $result = $reader->readRaw()->toString();
            }
            $tag = $stream->getc();
            if ($tag === Tags::TagArgument) {
                $reader->reset();
                $arguments = $reader->readList();
                $n = min(count($arguments), count($args));
                for ($i = 0; $i < $n; $i++) {
                    $args[$i] = $arguments[$i];
                }
                $tag = $stream->getc();
            }
        }
        elseif ($tag === Tags::TagError) {
            throw new Exception($reader->readString());
        }
        if ($tag !== Tags::TagEnd) {
            throw new Exception("Wrong Response: \r\n$response");
        }
        return $result;
    }

    private function getContext(InvokeSettings $settings) {
        $context = new stdClass();
        $context->client = $this;
        $context->userdata = isset($settings->userdata) ? (object)($settings->userdata) : new stdClass();
        $context->mode = isset($settings->mode) ? $settings->mode : ResultMode::Normal;
        $context->oneway = isset($settings->oneway) ? $settings->oneway : false;
        $context->byref = isset($settings->byref) ? $settings->byref : $this->byref;
        $context->simple = isset($settings->simple) ? $settings->simple : $this->simple;
        $context->failswitch = isset($settings->failswitch) ? $settings->failswitch : $this->failswitch;
        $context->idempontent = isset($settings->idempontent) ? $settings->idempontent : $this->idempontent;
        $context->retry = isset($settings->retry) ? $settings->retry : $this->retry;
        $context->timeout = isset($settings->timeout) ? $settings->timeout : $this->timeout;
        return $context;
    }

    public function __call($name, array $args) {
        $n = count($args);
        if ($n > 0) {
            if (is_callable($args[$n - 1])) {
                $callback = array_pop($args);
                return $this->invoke($name, $args, $callback);
            }
            else if ($args[$n - 1] instanceof InvokeSettings) {
                if (($n > 1) && is_callable($args[$n - 2])) {
                    $settings = array_pop($args); 
                    $callback = array_pop($args);
                    return $this->invoke($name, $args, $callback, $settings);
                }
                $settings = array_pop($args);
                return $this->invoke($name, $args, $settings);
            }
        }
        return $this->invoke($name, $args);
    }

    public function __get($name) {
        return new Proxy($this, $name . '_');
    }

    protected function getNextInvokeHandler(Closure $next, /*callable*/ $handler) {
        if ($this->async) return parent::getNextInvokeHandler($next, $handler);
        return function($name, array $args, stdClass $context) use ($next, $handler) {
            return call_user_func($handler, $name, $args, $context, $next);
        };
    }
    protected function getNextFilterHandler(Closure $next, /*callable*/ $handler) {
        if ($this->async) return parent::getNextFilterHandler($next, $handler);
        return function($request, stdClass $context) use ($next, $handler) {
            return call_user_func($handler, $request, $context, $next);
        };
    }

    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function sendRequest($request, stdClass $context) {
        $beforeFilterHandler = $this->beforeFilterHandler;
        if ($this->async) {
            $self = $this;
            return $beforeFilterHandler($request, $context)->catchError(function($e) use ($self, $request, $context) {
                $response = $self->retry($request, $context);
                if ($response !== null) {
                    return $response;
                }
                throw $e;
            });
        }
        $error = null;
        try {
            $response = $beforeFilterHandler($request, $context);
        }
        catch (Exception $e) { $error = $e; }
        catch (Throwable $e) { $error = $e; }
        if ($error !== null) {
            $response = $this->retry($request, $context);
            if ($response !== null) {
                return $response;
            }
            throw $error;
        }
        return $response;
    }

    private function asyncInvokeHandler($name, array &$args, stdClass $context) {
        try {
            $request = $this->encode($name, $args, $context);
        }
        catch (Exception $e) {
            return Future\error($e);
        }
        catch (Throwable $e) {
            return Future\error($e);
        }
        $self = $this;
        return $this->sendRequest($request, $context)->then(function($response) use ($self, &$args, $context) {
            return $self->decode($response, $args, $context);
        });
    }

    private function syncInvokeHandler($name, array &$args, stdClass $context) {
        $request = $this->encode($name, $args, $context);
        $response = $this->sendRequest($request, $context);
        return $this->decode($response, $args, $context);
    }

    /*
        This method is a protected method.
        But PHP 5.3 can't call protected method in closure,
        so we comment the protected keyword.
    */
    /*protected*/ function invokeHandler($name, array &$args, stdClass $context) {
        if ($this->async) {
            return $this->asyncInvokeHandler($name, $args, $context);
        }
        return $this->syncInvokeHandler($name, $args, $context);
    }

    private function asyncBeforeFilterHandler($request, stdClass $context) {
        $afterFilterHandler = $this->afterFilterHandler;
        $self = $this;
        return $afterFilterHandler($this->outputFilter($request, $context), $context)
                ->then(function($response) use ($self, $context) {
            if ($context->oneway) return null;
            return $self->inputFilter($response, $context);
        });
    }

    private function syncBeforeFilterHandler($request, stdClass $context) {
        $afterFilterHandler = $this->afterFilterHandler;
        $response = $afterFilterHandler($this->outputFilter($request, $context), $context);
        if ($context->oneway) return null;
        return $this->inputFilter($response, $context);
    }

    /*
        This method is a protected method.
        But PHP 5.3 can't call protected method in closure,
        so we comment the protected keyword.
    */
    /*protected*/ function beforeFilterHandler($request, stdClass $context) {
        if ($this->async) {
            return $this->asyncBeforeFilterHandler($request, $context);
        }
        return $this->syncBeforeFilterHandler($request, $context);
    }

    /*
        This method is a protected method.
        But PHP 5.3 can't call protected method in closure,
        so we comment the protected keyword.
    */
    /*protected*/ function afterFilterHandler($request, stdClass $context) {
        return $this->sendAndReceive($request, $context);
    }

    public function invoke($name, array $args = array(), $callback = null, InvokeSettings $settings = null) {
        if ($callback instanceof InvokeSettings) {
            $settings = $callback;
            $callback = null;
        }
        if ($settings === null) $settings = new InvokeSettings();
        $context = $this->getContext($settings);
        $invokeHandler = $this->invokeHandler;
        if (is_callable($callback)) {
            if (is_array($callback)) {
                $f = new ReflectionMethod($callback[0], $callback[1]);
            }
            else {
                $f = new ReflectionFunction($callback);
            }
            $n = $f->getNumberOfParameters();
            return Future\all($args)->then(function($args) use ($invokeHandler, $name, $context, $n, $callback) {
                $result = Future\toPromise($invokeHandler($name, $args, $context));
                $result->then(
                    function($result) use ($n, $callback, $args) {
                        switch($n) {
                            case 0: call_user_func($callback); break;
                            case 1: call_user_func($callback, $result); break;
                            case 2: call_user_func($callback, $result, $args); break;
                            case 3: call_user_func($callback, $result, $args, null); break;
                        }
                    },
                    function($error) use ($n, $callback, $args) {
                        switch($n) {
                            case 0: call_user_func($callback); break;
                            case 1: call_user_func($callback, $error); break;
                            case 2: call_user_func($callback, $error, $args); break;
                            case 3: call_user_func($callback, null, $args, $error); break;
                        }
                    }
                );
                return $result;
            });
        }
        else {
            if ($this->async) {
                $args = Future\all($args);
                return $args->then(function($args) use ($invokeHandler, $name, $context) {
                    return $invokeHandler($name, $args, $context);
                });
            }
            return $invokeHandler($name, $args, $context);
        }
    }

    protected abstract function sendAndReceive($request, stdClass $context);

}
