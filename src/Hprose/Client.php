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
 * LastModified: Aug 20, 2017                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

use stdClass;
use Closure;
use Exception;
use Throwable;
use TypeError;
use ReflectionMethod;
use ReflectionFunction;

abstract class Client extends HandlerManager {
    private $index = -1;
    private $uriList = null;
    protected $async = true;
    public $uri = null;
    public $filters = array();
    public $timeout = 30000;
    public $retry = 10;
    public $idempotent = false;
    public $failswitch = false;
    public $failround = 0;
    public $byref = false;
    public $simple = false;
    public $onError = null;
    public $onFailswitch = null;
    private $methodCache = array();

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
        self::tryRegisterClientFactory("http", "\\Hprose\\Http\\Client");
        self::tryRegisterClientFactory("https", "\\Hprose\\Http\\Client");
        self::tryRegisterClientFactory("tcp", "\\Hprose\\Socket\\Client");
        self::tryRegisterClientFactory("ssl", "\\Hprose\\Socket\\Client");
        self::tryRegisterClientFactory("sslv2", "\\Hprose\\Socket\\Client");
        self::tryRegisterClientFactory("sslv3", "\\Hprose\\Socket\\Client");
        self::tryRegisterClientFactory("tls", "\\Hprose\\Socket\\Client");
        self::tryRegisterClientFactory("unix", "\\Hprose\\Socket\\Client");
        self::$clientFactoriesInited = true;
    }

    public static function create($uriList, $async = true) {
        if (!self::$clientFactoriesInited) self::initClientFactories();
        if (is_string($uriList)) $uriList = array($uriList);
        $scheme = strtolower(parse_url($uriList[0], PHP_URL_SCHEME));
        $n = count($uriList);
        for ($i = 1; $i < $n; ++$i) {
            if (strtolower(parse_url($uriList[$i], PHP_URL_SCHEME)) != $scheme) {
                throw new Exception("Not support multiple protocol.");
            }
        }
        $clientFactory = self::$clientFactories[$scheme];
        if (empty($clientFactory)) {
            throw new Exception("This client doesn't support $scheme scheme.");
        }
        return new $clientFactory($uriList, $async);
    }

    public function __construct($uriList = null, $async = true) {
        parent::__construct();
        if ($uriList != null) {
            $this->setUriList($uriList);
            if (is_bool($uriList)) {
                $async = $uriList;
            }
        }
        $this->async = $async;
    }

    public function __destruct() {
        $this->close();
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

    public final function isIdempotent() {
        return $this->idempotent;
    }

    public final function setIdempotent($idempotent) {
        $this->idempotent = $idempotent;
    }

    public final function isFailswitch() {
        return $this->failswitch;
    }

    public final function setFailswitch($failswitch) {
        $this->failswitch = $failswitch;
    }

    public final function getFailround() {
        return $this->failround;
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
        return $this;
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

    public function getUriList() {
        return $this->uriList;
    }

    public function setUriList($uriList) {
        if (is_string($uriList)) {
            $uriList = array($uriList);
        }
        else if (is_array($uriList)) {
            shuffle($uriList);
        }
        else {
            return;
        }
        $this->index = 0;
        $this->failround = 0;
        $this->uriList = $uriList;
        $this->setUri($uriList[$this->index]);
    }

    public function useService($uriList = array(), $namespace = '') {
        if (!empty($uriList)) {
            $this->setUriList($uriList);
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
        $n = count($this->uriList);
        if ($n > 1) {
            $i = $this->index + 1;
            if ($i >= $n) {
                $i = 0;
                $this->failround++;
            }
            $this->index = $i;
            $this->setUri($this->uriList[$i]);
        }
        else {
            $this->failround++;
        }
        $onFailswitch = $this->onFailswitch;
        if (is_callable($onFailswitch)) {
            call_user_func($onFailswitch, $this);
        }
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
        if ($context->idempotent && ($context->retried < $context->retry)) {
            $interval = ++$context->retried * 0.5;
            if ($context->failswitch) {
                $interval -= (count($this->uriList) - 1) * 0.5;
            }
            if ($interval > 5) $interval = 5;
            $self = $this;
            if ($interval > 0) {
                return $this->wait($interval, function() use ($self, $request, $context) {
                    return $self->afterFilterHandler($request, $context);
                });
            }
            else {
                return $this->afterFilterHandler($request, $context);
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
            $e = new Exception($reader->readString());
            $stream->close();
            throw $e;
        }
        if ($tag !== Tags::TagEnd) {
            $stream->close();
            throw new Exception("Wrong Response: \r\n$response");
        }
        $stream->close();
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
        $context->idempotent = isset($settings->idempotent) ? $settings->idempotent : $this->idempotent;
        $context->retry = isset($settings->retry) ? $settings->retry : $this->retry;
        $context->retried = 0;
        $context->timeout = isset($settings->timeout) ? $settings->timeout : $this->timeout;
        return $context;
    }

    public function __call($name, array $args) {
        if (isset($this->methodCache[$name])) {
            $method = $this->methodCache[$name];
            return call_user_func_array($method, $args);
        }
        $n = count($args);
        if ($n > 0) {
            if ($args[$n - 1] instanceof Closure) {
                $callback = array_pop($args);
                return $this->invoke($name, $args, $callback);
            }
            else if ($args[$n - 1] instanceof InvokeSettings) {
                if (($n > 1) && ($args[$n - 2] instanceof Closure)) {
                    $settings = array_pop($args);
                    $callback = array_pop($args);
                    return $this->invoke($name, $args, $callback, $settings);
                }
                $settings = array_pop($args);
                return $this->invoke($name, $args, $settings);
            }
            else if (($n > 1) && is_array($args[$n - 1]) &&
                    ($args[$n - 2] instanceof Closure)) {
                $settings = new InvokeSettings(array_pop($args));
                $callback = array_pop($args);
                return $this->invoke($name, $args, $callback, $settings);
            }
        }
        return $this->invoke($name, $args);
    }

    public function __get($name) {
        if (isset($this->methodCache[$name])) {
            return $this->methodCache[$name];
        }
        $method = new Proxy($this, $name . '_');
        $this->methodCache[$name] = $method;
        return $method;
    }

    protected function getNextInvokeHandler(Closure $next, /*callable*/ $handler) {
        if ($this->async) return parent::getNextInvokeHandler($next, $handler);
        return function($name, array &$args, stdClass $context) use ($next, $handler) {
            $array = array($name, &$args, $context, $next);
            return call_user_func_array($handler, $array);
        };
    }
    protected function getNextFilterHandler(Closure $next, /*callable*/ $handler) {
        if ($this->async) return parent::getNextFilterHandler($next, $handler);
        return function($request, stdClass $context) use ($next, $handler) {
            return call_user_func($handler, $request, $context, $next);
        };
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
        $beforeFilterHandler = $this->beforeFilterHandler;
        return $beforeFilterHandler($request, $context)->then(function($response) use ($self, &$args, $context) {
            return $self->decode($response, $args, $context);
        });
    }

    private function syncInvokeHandler($name, array &$args, stdClass $context) {
        $request = $this->encode($name, $args, $context);
        $beforeFilterHandler = $this->beforeFilterHandler;
        $response = $beforeFilterHandler($request, $context);
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
        if ($this->async) {
            $self = $this;
            return $this->sendAndReceive($request, $context)->catchError(function($e) use ($self, $request, $context) {
                $response = $self->retry($request, $context);
                if ($response !== null) {
                    return $response;
                }
                throw $e;
            });
        }
        $error = null;
        try {
            $response = $this->sendAndReceive($request, $context);
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

    public function invoke($name, array &$args = array(), $callback = null, InvokeSettings $settings = null) {
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
            $onError = $this->onError;
            return Future\all($args)->then(function($args) use ($invokeHandler, $name, $context, $n, $callback, $onError) {
                try {
                    $result = Future\toFuture($invokeHandler($name, $args, $context));
                }
                catch (Exception $e) { $result = Future\error($e); }
                catch (Throwable $e) { $result = Future\error($e); }
                $result->then(
                    function($result) use ($n, $callback, $args) {
                        switch($n) {
                            case 0: call_user_func($callback); break;
                            case 1: call_user_func($callback, $result); break;
                            case 2: call_user_func($callback, $result, $args); break;
                            case 3: call_user_func($callback, $result, $args, null); break;
                        }
                    },
                    function($error) use ($n, $callback, $args, $name, $onError) {
                        switch($n) {
                            case 0:
                                if (is_callable($onError)) {
                                    call_user_func($onError, $name, $error);
                                }
                                break;
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

    private $topics;
    private $id;
    private function autoId() {
        $settings = new InvokeSettings(array(
            'idempotent' => true,
            'failswitch' => true
        ));
        $args = array();
        return Future\toFuture($this->invoke('#', $args, $settings));
    }
    public function getId() {
        if ($this->id == null) {
            $this->id = $this->autoId();
        }
        return $this->id;
    }
    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function getTopic($name, $id) {
        if (isset($this->topics[$name])) {
            $topics = $this->topics[$name];
            if (isset($topics[$id])) {
                return $topics[$id];
            }
        }
        return null;
    }
    // subscribe($name, $callback, $timeout, $failswitch)
    // subscribe($name, $id, $callback, $timeout, $failswitch)
    public function subscribe($name, $id = null, $callback = null, $timeout = null, $failswitch = false) {
        $self = $this;
        if (!is_string($name)) {
            throw new TypeError('topic name must be a string');
        }
        if (is_callable($id) && !is_callable($callback)) {
            $timeout = $callback;
            $callback = $id;
            $id = null;
        }
        if (!is_callable($callback)) {
            throw new TypeError('callback must be a function.');
        }
        if (!isset($this->topics[$name])) {
            $this->topics[$name] = array();
        }
        if ($id === null) {
            if ($this->id == null) {
                $this->id = $this->autoId();
            }
            $this->id->then(function($id) use ($self, $name, $callback, $timeout, $failswitch) {
                $self->subscribe($name, $id, $callback, $timeout, $failswitch);
            });
            return;
        }
        // Default subscribe timeout is 5 minutes.
        if (!is_int($timeout)) $timeout = 300000;
        $topic = $this->getTopic($name, $id);
        if ($topic === null) {
            $topic = new stdClass();
            $settings = new InvokeSettings(array(
                'idempotent' => true,
                'failswitch' => $failswitch,
                'timeout' => $timeout
            ));
            $cb = function() use ($self, &$cb, $topic, $name, $id, $settings) {
                $args = array($id);
                $self->invoke($name, $args, $settings)
                     ->then($topic->handler, $cb);
            };
            $topic->handler = function($result) use ($self, $name, $id, $cb) {
                $topic = $self->getTopic($name, $id);
                if ($topic !== null) {
                    if ($result !== null) {
                        $callbacks = $topic->callbacks;
                        foreach ($callbacks as $callback) {
                            try {
                                call_user_func($callback, $result);
                            }
                            catch (Exception $ex) {}
                            catch (Throwable $ex) {}
                        }
                    }
                    if ($self->getTopic($name, $id) !== null) $cb();
                }
            };
            $topic->callbacks = array($callback);
            $this->topics[$name][$id] = $topic;
            $cb();
        }
        elseif (array_search($callback, $topic->callbacks, true) === false) {
            $topic->callbacks[] = $callback;
        }
    }
    private function delTopic(&$topics, $id, $callback) {
        if ($topics !== null) {
            if (is_callable($callback)) {
                if (isset($topics[$id])) {
                    $topic = $topics[$id];
                    $callbacks = array_diff($topic->callbacks, array($callback));
                    if (count($callbacks) > 0) {
                        $topic->callbacks = $callbacks;
                    }
                    else {
                        unset($topics[$id]);
                    }
                }
            }
            else {
                unset($topics[$id]);
            }
        }
    }
    // unsubscribe($name)
    // unsubscribe($name, $callback)
    // unsubscribe($name, $id)
    // unsubscribe($name, $id, $callback)
    public function unsubscribe($name, $id = null, $callback = null) {
        $self = $this;
        if (!is_string($name)) {
            throw new TypeError('topic name must be a string');
        }
        if (($id === null) && ($callback === null)) {
            unset($this->topics[$name]);
            return;
        }
        if (is_callable($id) && !is_callable($callback)) {
            $callback = $id;
            $id = null;
        }
        if ($id === null) {
            if ($this->id === null) {
                if (isset($this->topics[$name])) {
                    $topics = $this->topics[$name];
                    $ids = array_keys($topics);
                    foreach ($ids as $id) {
                        $this->delTopic($topics, $id, $callback);
                    }
                }
            }
            else {
                $this->id->then(function($id) use ($self, $name, $callback) {
                    $self->unsubscribe($name, $id, $callback);
                });
            }
        }
        elseif (Future\isFuture($id)) {
            $id->then(function($id) use ($self, $name, $callback) {
                $self->unsubscribe($name, $id, $callback);
            });
        }
        else {
            $this->delTopic($this->topics[$name], $id, $callback);
        }
        if (isset($this->topics[$name]) && count($this->topics[$name]) === 0) {
            unset($this->topics[$name]);
        }
    }

    public function isSubscribed($name) {
        return isset($this->topics[$name]);
    }

    public function subscribedList() {
        return array_keys($this->topics);
    }
}