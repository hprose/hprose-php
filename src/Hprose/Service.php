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
 * Hprose/Service.php                                     *
 *                                                        *
 * hprose service class for php 5.3+                      *
 *                                                        *
 * LastModified: Jul 14, 2017                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

use stdClass;
use ErrorException;
use Exception;
use Throwable;
use ArrayObject;
use SplQueue;
use ReflectionMethod;
use ReflectionFunction;

abstract class Service extends HandlerManager {
    private static $magicMethods = array(
        "__construct",
        "__destruct",
        "__call",
        "__callStatic",
        "__get",
        "__set",
        "__isset",
        "__unset",
        "__sleep",
        "__wakeup",
        "__toString",
        "__invoke",
        "__set_state",
        "__clone"
    );
    private $calls = array();
    private $names = array();
    private $filters = array();
    protected $userFatalErrorHandler = null;
    public $onBeforeInvoke = null;
    public $onAfterInvoke = null;
    public $onSendError = null;
    public $errorDelay = 10000;
    public $errorTypes;
    public $simple = false;
    public $debug = false;
    public $passContext = false;
    // for push service
    protected $timer = null;
    public $timeout = 120000;
    public $heartbeat = 3000;
    public $onSubscribe = null;
    public $onUnsubscribe = null;
    private $topics = array();
    private $nextid = 0;
    public function __construct() {
        parent::__construct();
        $this->errorTypes = error_reporting();
        register_shutdown_function(array($this, 'fatalErrorHandler'));
        $this->addMethod('getNextId', $this, '#', array('simple' => true));
    }
    public function getNextId() {
        if (function_exists('com_create_guid')) {
            return trim(com_create_guid(), '{}');
        }
        return md5(uniqid(dechex(mt_rand()), true) . dechex(mt_rand()));
    }
    public function fatalErrorHandler() {
        if (!is_callable($this->userFatalErrorHandler)) return;
        $e = error_get_last();
        if ($e == null) return;
        switch ($e['type']) {
            case E_ERROR:
            case E_PARSE:
            case E_USER_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR: {
                $error = new ErrorException($e['message'], 0, $e['type'], $e['file'], $e['line']);
                @ob_end_clean();
                $userFatalErrorHandler = $this->userFatalErrorHandler;
                call_user_func($userFatalErrorHandler, $error);
            }
        }
    }
    public final function getTimeout() {
        return $this->timeout;
    }
    public final function setTimeout($value) {
        $this->timeout = $value;
    }
    public final function getHeartbeat() {
        return $this->heartbeat;
    }
    public final function setHeartbeat($value) {
        $this->heartbeat = $value;
    }
    public final function getErrorDelay() {
        return $this->errorDelay;
    }
    public final function setErrorDelay($value) {
        $this->errorDelay = $value;
    }
    public final function getErrorTypes() {
        return $this->errorTypes;
    }
    public final function setErrorTypes($value) {
        $this->errorTypes = $value;
    }
    public final function isDebugEnabled() {
        return $this->debug;
    }
    public final function setDebugEnabled($value = true) {
        $this->debug = $value;
    }
    public final function isSimple() {
        return $this->simple;
    }
    public final function setSimple($value = true) {
        $this->simple = $value;
    }
    public final function isPassContext() {
        return $this->passContext;
    }
    public final function setPassContext($value = true) {
        $this->passContext = $value;
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
    protected function nextTick($callback) {
        $callback();
    }
    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function callService(array $args, stdClass $context) {
        if ($context->oneway) {
            $this->nextTick(function() use ($args, $context) {
                try {
                    call_user_func_array($context->method, $args);
                }
                catch (Exception $e) {}
                catch (Throwable $e) {}
            });
            if ($context->async) {
                call_user_func($args[count($args) - 1], null);
            }
            return null;
        }
        return call_user_func_array($context->method, $args);
    }
    private function inputFilter($data, stdClass $context) {
        for ($i = count($this->filters) - 1; $i >= 0; $i--) {
            $data = $this->filters[$i]->inputFilter($data, $context);
        }
        return $data;
    }
    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function outputFilter($data, stdClass $context) {
        for ($i = 0, $n = count($this->filters); $i < $n; $i++) {
            $data = $this->filters[$i]->outputFilter($data, $context);
        }
        return $data;
    }

    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function sendError($error, stdClass $context) {
        if (is_string($error)) {
            $error = new Exception($error);
        }
        try {
            if ($this->onSendError !== null) {
                $onSendError = $this->onSendError;
                $e = call_user_func_array($onSendError, array(&$error, $context));
                if ($e instanceof Exception || $e instanceof Throwable) {
                    $error = $e;
                }
            }
        }
        catch (Exception $e) {
            $error = $e;
        }
        $stream = new BytesIO();
        $writer = new Writer($stream, true);
        $stream->write(Tags::TagError);
        $errmsg = $error->getMessage();
        if ($this->debug) {
            $errmsg .= "\r\n" . $error->getTraceAsString();
        }
        $writer->writeString($errmsg);
        return $stream;
    }
    public function endError($error, stdClass $context) {
        $stream = $this->sendError($error, $context);
        $stream->write(Tags::TagEnd);
        $data = $stream->toString();
        $stream->close();
        return $data;
    }
    private function beforeInvoke($name, array &$args, stdClass $context) {
        try {
            $self = $this;
            if ($this->onBeforeInvoke !== null) {
                $onBeforeInvoke = $this->onBeforeInvoke;
                $value = call_user_func_array($onBeforeInvoke, array($name, &$args, $context->byref, $context));
                if ($value instanceof Exception || $value instanceof Throwable) {
                    throw $value;
                }
                if (Future\isFuture($value)) {
                    return $value->then(function($value) use ($self, $name, $args, $context) {
                        if ($value instanceof Exception || $value instanceof Throwable) {
                            throw $value;
                        }
                        return $self->invoke($name, $args, $context);
                    })->then(null, function($error) use ($self, $context) {
                        return $self->sendError($error, $context);
                    });
                }
            }
            return $this->invoke($name, $args, $context)->then(null, function($error) use ($self, $context) {
                return $self->sendError($error, $context);
            });
        }
        catch (Exception $error) {
            return $this->sendError($error, $context);
        }
    }
    /*
        This method is a protected method.
        But PHP 5.3 can't call protected method in closure,
        so we comment the protected keyword.
    */
    /*protected*/ function invokeHandler($name, array &$args, stdClass $context) {
        if ($context->isMissingMethod) {
            $args = array($name, $args);
        }
        $passContext = $context->passContext;
        if ($passContext === null) {
            $passContext = $this->passContext;
        }
        if ($context->async) {
            $self = $this;
            return Future\promise(function($resolve, $reject) use ($self, $passContext, &$args, $context) {
                if ($passContext) $args[] = $context;
                $args[] = function($value) use ($resolve, $reject) {
                    if ($value instanceof Exception || $value instanceof Throwable) {
                        $reject($value);
                    }
                    else {
                        $resolve($value);
                    }
                };
                $self->callService($args, $context);
            });
        }
        else {
            if ($passContext) $args[] = $context;
            return Future\toFuture($this->callService($args, $context));
        }
    }
    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function invoke($name, array &$args, stdClass $context) {
        $invokeHandler = $this->invokeHandler;
        $self = $this;
        return $invokeHandler($name, $args, $context)
                ->then(function($value) use ($self, $name, &$args, $context) {
                    if ($value instanceof Exception || $value instanceof Throwable) {
                        throw $value;
                    }
                    return $self->afterInvoke($name, $args, $context, $value);
                });
    }
    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function afterInvoke($name, array $args, stdClass $context, $result) {
        if ($context->async && is_callable($args[count($args) - 1])) {
            unset($args[count($args) - 1]);
        }
        if ($context->passContext && ($args[count($args) - 1] === $context)) {
            unset($args[count($args) - 1]);
        }
        if ($this->onAfterInvoke !== null) {
            $onAfterInvoke = $this->onAfterInvoke;
            $value = call_user_func_array($onAfterInvoke, array($name, &$args, $context->byref, &$result, $context));
            if ($value instanceof Exception || $value instanceof Throwable) {
                throw $value;
            }
            if (Future\isFuture($value)) {
                $self = $this;
                return $value->then(function($value) use ($self, $args, $context, $result) {
                    if ($value instanceof Exception || $value instanceof Throwable) {
                        throw $value;
                    }
                    return $self->doOutput($args, $context, $result);
                });
            }
        }
        return $this->doOutput($args, $context, $result);
    }
    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function doOutput(array $args, stdClass $context, $result) {
        $mode = $context->mode;
        $simple = $context->simple;
        if ($simple === null) {
            $simple = $this->simple;
        }
        if ($mode === ResultMode::RawWithEndTag || $mode == ResultMode::Raw) {
            return $result;
        }
        $stream = new BytesIO();
        $writer = new Writer($stream, $simple);
        $stream->write(Tags::TagResult);
        if ($mode === ResultMode::Serialized) {
            $stream->write($result);
        }
        else {
            $writer->reset();
            $writer->serialize($result);
        }
        if ($context->byref) {
            $stream->write(Tags::TagArgument);
            $writer->reset();
            $writer->writeArray($args);
        }
        $data = $stream->toString();
        $stream->close();
        return $data;
    }
    private function doInvoke(BytesIO $stream, stdClass $context) {
        $results = array();
        $reader = new Reader($stream);
        do {
            $reader->reset();
            $name = $reader->readString();
            $alias = strtolower($name);
            $cc = new stdClass();
            $cc->isMissingMethod = false;
            foreach ($context as $key => $value) {
                $cc->$key = $value;
            }
            $call = false;
            if (isset($this->calls[$alias])) {
                $call = $this->calls[$alias];
            }
            else if (isset($this->calls['*'])) {
                $call = $this->calls['*'];
                $cc->isMissingMethod = true;
            }
            if ($call) {
                foreach ($call as $key => $value) {
                    $cc->$key = $value;
                }
            }
            $args = array();
            $cc->byref = false;
            $tag = $stream->getc();
            if ($tag === Tags::TagList) {
                $reader->reset();
                $args = $reader->readListWithoutTag();
                $tag = $stream->getc();
                if ($tag === Tags::TagTrue) {
                    $cc->byref = true;
                    $arguments = array();
                    foreach ($args as &$value) {
                        $arguments[] = &$value;
                    }
                    $args = $arguments;
                    $tag = $stream->getc();
                }
            }
            if ($tag !== Tags::TagEnd && $tag !== Tags::TagCall) {
                $data = $stream->toString();
                throw new Exception("Unknown tag: $tag\r\nwith following data: $data");
            }
            if ($call) {
                $results[] = $this->beforeInvoke($name, $args, $cc);
            }
            else {
                $results[] = $this->sendError(new Exception("Can\'t find this function $name()."), $cc);
            }
        } while($tag === Tags::TagCall);
        return Future\reduce($results, function($stream, $result) {
            $stream->write($result);
            return $stream;
        }, new BytesIO())->then(function($stream) {
            $stream->write(Tags::TagEnd);
            $data = $stream->toString();
            $stream->close();
            return $data;
        });
    }
    protected function doFunctionList() {
        $stream = new BytesIO();
        $writer = new Writer($stream, true);
        $stream->write(Tags::TagFunctions);
        $writer->writeArray($this->names);
        $stream->write(Tags::TagEnd);
        $data = $stream->toString();
        $stream->close();
        return $data;
    }
    protected function delay($interval, $data) {
        $seconds = floor($interval);
        $nanoseconds = ($interval - $seconds) * 1000000000;
        time_nanosleep($seconds, $nanoseconds);
        return Future\value($data);
    }
    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function delayError($error, $context) {
        $err = $this->endError($error, $context);
        if ($this->errorDelay > 0) {
            return $this->delay($this->errorDelay, $err);
        }
        return Future\value($err);
    }
     /*
        This method is a protected method.
        But PHP 5.3 can't call protected method in closure,
        so we comment the protected keyword.
    */
    /*protected*/ function beforeFilterHandler($request, stdClass $context) {
        $self = $this;
        try {
            $afterFilterHandler = $this->afterFilterHandler;
            $response = $afterFilterHandler($this->inputFilter($request, $context), $context)
                    ->then(null, function($error) use ($self, $context) {
                        return $self->delayError($error, $context);
                    });
        }
        catch (Exception $error) {
            $response = $this->delayError($error, $context);
        }
        return $response->then(function($value) use ($self, $context) {
            return $self->outputFilter($value, $context);
        });
    }
    /*
        This method is a protected method.
        But PHP 5.3 can't call protected method in closure,
        so we comment the protected keyword.
    */
    /*protected*/ function afterFilterHandler($request, stdClass $context) {
        $stream = new BytesIO($request);
        try {
            switch ($stream->getc()) {
                case Tags::TagCall: {
                    $data = $this->doInvoke($stream, $context);
                    $stream->close();
                    return $data;
                }
                case Tags::TagEnd: {
                    $stream->close();
                    return Future\value($this->doFunctionList());
                }
                default: throw new Exception("Wrong Request: \r\n$request");
            }
        }
        catch (Exception $e) {
            $stream->close();
            return Future\error($e);
        }
    }
    public function defaultHandle($request, stdClass $context) {
        $error = null;
        set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$error) {
            $error = new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }, $this->errorTypes);
        ob_start();
        ob_implicit_flush(0);
        $context->clients = $this;
        $context->methods = $this->calls;
        $beforeFilterHandler = $this->beforeFilterHandler;
        $response = $beforeFilterHandler($request, $context);
        $self = $this;
        return $response->then(function($result) use ($self, &$error, $context) {
            @ob_end_clean();
            restore_error_handler();
            if ($error === null) {
                return $result;
            }
            return $self->endError($error, $context);
        });
    }
    private static function getDeclaredOnlyMethods($class) {
        $result = get_class_methods($class);
        if (($parentClass = get_parent_class($class)) !== false) {
            $inherit = get_class_methods($parentClass);
            $result = array_diff($result, $inherit);
        }
        return array_diff($result, self::$magicMethods);
    }
    private static function getDeclaredOnlyInstanceMethods($class) {
        $methods = self::getDeclaredOnlyMethods($class);
        $instanceMethods = array();
        foreach ($methods as $name) {
            $method = new ReflectionMethod($class, $name);
            if ($method->isPublic() &&
                !$method->isStatic() &&
                !$method->isConstructor() &&
                !$method->isDestructor() &&
                !$method->isAbstract()) {
                $instanceMethods[] = $name;
            }
        }
        if (empty($instanceMethods)) {
            throw new Exception('There is no pubic instance method in class $class.');
        }
        return $instanceMethods;
    }
    private static function getDeclaredOnlyStaticMethods($class) {
        $methods = self::getDeclaredOnlyMethods($class);
        $instanceMethods = array();
        foreach ($methods as $name) {
            $method = new ReflectionMethod($class, $name);
            if ($method->isPublic() &&
                $method->isStatic() &&
                !$method->isAbstract()) {
                $instanceMethods[] = $name;
            }
        }
        if (empty($instanceMethods)) {
            throw new Exception('There is no pubic static method in class $class.');
        }
        return $instanceMethods;
    }
    public function addFunction($func, $alias = '', array $options = array()) {
        if (!is_callable($func)) {
            throw new Exception('Argument func must be callable.');
        }
        if (is_array($alias) && empty($options)) {
            $options = $alias;
            $alias = '';
        }
        if (empty($alias)) {
            if (is_string($func)) {
                $alias = $func;
            }
            elseif (is_array($func)) {
                $alias = $func[1];
            }
            else {
                throw new Exception('Need an alias');
            }
        }
        $name = strtolower($alias);
        if (!array_key_exists($name, $this->calls)) {
            $this->names[] = $alias;
        }
        if (class_exists("\\Generator")) {
            if (is_array($func)) {
                $f = new ReflectionMethod($func[0], $func[1]);
            }
            else {
                $f = new ReflectionFunction($func);
            }
            if ($f->isGenerator()) {
                $func = Future\wrap($func);
            }
        }
        $call = new stdClass();
        $call->method = $func;
        $call->mode = isset($options['mode']) ? $options['mode'] : ResultMode::Normal;
        $call->simple = isset($options['simple']) ? $options['simple'] : null;
        $call->oneway = isset($options['oneway']) ? $options['oneway'] : false;
        $call->async = isset($options['async']) ? $options['async'] : false;
        $call->passContext = isset($options['passContext']) ? $options['passContext']: null;
        $this->calls[$name] = $call;
        return $this;
    }
    public function addAsyncFunction($func,
                                     $alias = '',
                                     array $options = array()) {
        if (is_array($alias) && empty($options)) {
            $options = $alias;
            $alias = '';
        }
        $options['async'] = true;
        return $this->addFunction($func, $alias, $options);
    }
    public function addMissingFunction($func, array $options = array()) {
        return $this->addFunction($func, '*', $options);
    }
    public function addAsyncMissingFunction($func, array $options = array()) {
        return $this->addAsyncFunction($func, '*', $options);
    }
    public function addFunctions(array $funcs,
                                 array $aliases = array(),
                                 array $options = array()) {
        if (!empty($aliases) && empty($options) && (array_keys($funcs) != array_keys($aliases))) {
            $options = $aliases;
            $aliases = array();
        }
        $count = count($aliases);
        if ($count == 0) {
            foreach ($funcs as $func) {
                $this->addFunction($func, '', $options);
            }
        }
        elseif ($count == count($funcs)) {
            foreach ($funcs as $i => $func) {
                $this->addFunction($func, $aliases[$i], $options);
            }
        }
        else {
            throw new Exception('The count of functions is not matched with aliases');
        }
        return $this;
    }
    public function addAsyncFunctions(array $funcs,
                                      array $aliases = array(),
                                      array $options = array()) {
        if (!empty($aliases) && empty($options) && (array_keys($funcs) != array_keys($aliases))) {
            $options = $aliases;
            $aliases = array();
        }
        $options['async'] = true;
        return $this->addFunctions($funcs, $aliases, $options);
    }
    public function addMethod($method,
                              $scope,
                              $alias = '',
                              array $options = array()) {
        $func = array($scope, $method);
        return $this->addFunction($func, $alias, $options);
    }
    public function addAsyncMethod($method,
                                   $scope,
                                   $alias = '',
                                   array $options = array()) {
        $func = array($scope, $method);
        return $this->addAsyncFunction($func, $alias, $options);
    }
    public function addMissingMethod($method, $scope, array $options = array()) {
        return $this->addMethod($method, $scope, '*', $options);
    }
    public function addAsyncMissingMethod($method, $scope, array $options = array()) {
        return $this->addAsyncMethod($method, $scope, '*', $options);
    }
    public function addMethods($methods,
                               $scope,
                               $aliases = array(),
                               array $options = array()) {
        $aliasPrefix = '';
        if (is_string($aliases)) {
            $aliasPrefix = $aliases;
            if ($aliasPrefix !== '') {
                $aliasPrefix .= '_';
            }
            $aliases = array();
        }
        else if (!empty($aliases) && empty($options) && (array_keys($methods) != array_keys($aliases))) {
            $options = $aliases;
            $aliases = array();
        }
        if (empty($aliases)) {
            foreach ($methods as $k => $method) {
                $aliases[$k] = $aliasPrefix . $method;
            }
        }
        if (count($methods) != count($aliases)) {
            throw new Exception('The count of methods is not matched with aliases');
        }
        foreach($methods as $k => $method) {
            $func = array($scope, $method);
            if (is_callable($func)) {
                $this->addFunction($func, $aliases[$k], $options);
            }
        }
        return $this;
    }
    public function addAsyncMethods($methods,
                                    $scope,
                                    $aliases = array(),
                                    array $options = array()) {
        $aliasPrefix = '';
        if (is_string($aliases)) {
            $aliasPrefix = $aliases;
            if ($aliasPrefix !== '') {
                $aliasPrefix .= '_';
            }
            $aliases = array();
        }
        else if (!empty($aliases) && empty($options) && (array_keys($methods) != array_keys($aliases))) {
            $options = $aliases;
            $aliases = array();
        }
        if (empty($aliases)) {
            foreach ($methods as $k => $method) {
                $aliases[$k] = $aliasPrefix . $method;
            }
        }
        if (count($methods) != count($aliases)) {
            throw new Exception('The count of methods is not matched with aliases');
        }
        foreach($methods as $k => $method) {
            $func = array($scope, $method);
            if (is_callable($func)) {
                $this->addAsyncFunction($func, $aliases[$k], $options);
            }
        }
        return $this;
    }
    public function addInstanceMethods($object,
                                       $class = '',
                                       $aliasPrefix = '',
                                       array $options = array()) {
        if ($class == '') {
            $class = get_class($object);
        }
        return $this->addMethods(self::getDeclaredOnlyInstanceMethods($class),
                                 $object, $aliasPrefix, $options);
    }
    public function addAsyncInstanceMethods($object,
                                            $class = '',
                                            $aliasPrefix = '',
                                            array $options = array()) {
        if ($class == '') {
            $class = get_class($object);
        }
        return $this->addAsyncMethods(self::getDeclaredOnlyInstanceMethods($class),
                                      $object, $aliasPrefix, $options);
    }
    public function addClassMethods($class,
                                    $scope = '',
                                    $aliasPrefix = '',
                                    array $options = array()) {
        if ($scope == '') {
            $scope = $class;
        }
        return $this->addMethods(self::getDeclaredOnlyStaticMethods($class),
                                 $scope, $aliasPrefix, $options);
    }
    public function addAsyncClassMethods($class,
                                         $scope = '',
                                         $aliasPrefix = '',
                                         array $options = array()) {
        if ($scope == '') {
            $scope = $class;
        }
        return $this->addAsyncMethods(self::getDeclaredOnlyStaticMethods($class),
                                      $scope, $aliasPrefix, $options);
    }
    public function add() {
        $args_num = func_num_args();
        $args = func_get_args();
        switch ($args_num) {
            case 1: {
                if (is_callable($args[0])) {
                    return $this->addFunction($args[0]);
                }
                elseif (is_array($args[0])) {
                    return $this->addFunctions($args[0]);
                }
                elseif (is_object($args[0])) {
                    return $this->addInstanceMethods($args[0]);
                }
                elseif (is_string($args[0])) {
                    return $this->addClassMethods($args[0]);
                }
                break;
            }
            case 2: {
                if (is_callable($args[0]) && is_string($args[1])) {
                    return $this->addFunction($args[0], $args[1]);
                }
                elseif (is_string($args[0])) {
                    if (is_string($args[1]) && !is_callable(array($args[1], $args[0]))) {
                        if (class_exists($args[1])) {
                            return $this->addClassMethods($args[0], $args[1]);
                        }
                        return $this->addClassMethods($args[0], '', $args[1]);
                    }
                    return $this->addMethod($args[0], $args[1]);
                }
                elseif (is_array($args[0])) {
                    if (is_array($args[1])) {
                        return $this->addFunctions($args[0], $args[1]);
                    }
                    return $this->addMethods($args[0], $args[1]);
                }
                elseif (is_object($args[0])) {
                    return $this->addInstanceMethods($args[0], $args[1]);
                }
                break;
            }
            case 3: {
                if (is_callable($args[0]) && $args[1] == '' && is_string($args[2])) {
                    return $this->addFunction($args[0], $args[2]);
                }
                elseif (is_string($args[0]) && is_string($args[2])) {
                    if (is_string($args[1]) && !is_callable(array($args[1], $args[0]))) {
                        return $this->addClassMethods($args[0], $args[1], $args[2]);
                    }
                    return $this->addMethod($args[0], $args[1], $args[2]);
                }
                elseif (is_array($args[0])) {
                    if ($args[1] == '' && is_array($args[2])) {
                        return $this->addFunctions($args[0], $args[2]);
                    }
                    return $this->addMethods($args[0], $args[1], $args[2]);
                }
                elseif (is_object($args[0])) {
                    return $this->addInstanceMethods($args[0], $args[1], $args[2]);
                }
                break;
            }
        }
        throw new Exception('Wrong arguments');
    }
    public function addAsync() {
        $args_num = func_num_args();
        $args = func_get_args();
        switch ($args_num) {
            case 1: {
                if (is_callable($args[0])) {
                    return $this->addAsyncFunction($args[0]);
                }
                elseif (is_array($args[0])) {
                    return $this->addAsyncFunctions($args[0]);
                }
                elseif (is_object($args[0])) {
                    return $this->addAsyncInstanceMethods($args[0]);
                }
                elseif (is_string($args[0])) {
                    return $this->addAsyncClassMethods($args[0]);
                }
                break;
            }
            case 2: {
                if (is_callable($args[0]) && is_string($args[1])) {
                    return $this->addAsyncFunction($args[0], $args[1]);
                }
                elseif (is_string($args[0])) {
                    if (is_string($args[1]) && !is_callable(array($args[1], $args[0]))) {
                        if (class_exists($args[1])) {
                            return $this->addAsyncClassMethods($args[0], $args[1]);
                        }
                        return $this->addAsyncClassMethods($args[0], '', $args[1]);
                    }
                    return $this->addAsyncMethod($args[0], $args[1]);
                }
                elseif (is_array($args[0])) {
                    if (is_array($args[1])) {
                        return $this->addAsyncFunctions($args[0], $args[1]);
                    }
                    return $this->addAsyncMethods($args[0], $args[1]);
                }
                elseif (is_object($args[0])) {
                    return $this->addAsyncInstanceMethods($args[0], $args[1]);
                }
                break;
            }
            case 3: {
                if (is_callable($args[0]) && $args[1] == '' && is_string($args[2])) {
                    return $this->addAsyncFunction($args[0], $args[2]);
                }
                elseif (is_string($args[0]) && is_string($args[2])) {
                    if (is_string($args[1]) && !is_callable(array($args[1], $args[0]))) {
                        return $this->addAsyncClassMethods($args[0], $args[1], $args[2]);
                    }
                    return $this->addAsyncMethod($args[0], $args[1], $args[2]);
                }
                elseif (is_array($args[0])) {
                    if ($args[1] == '' && is_array($args[2])) {
                        return $this->addAsyncFunctions($args[0], $args[2]);
                    }
                    return $this->addAsyncMethods($args[0], $args[1], $args[2]);
                }
                elseif (is_object($args[0])) {
                    return $this->addAsyncInstanceMethods($args[0], $args[1], $args[2]);
                }
                break;
            }
        }
        throw new Exception('Wrong arguments');
    }

    public function remove($alias) {
        $index = array_search($alias, $this->names, true);
        if ($index !== false) {
            array_splice($this->names, $index, 1);
            unset($this->calls[strtolower($alias)]);
        }
    }

// for push service
    private function checkPushService() {
        if ($this->timer === null) {
            throw new Exception(get_class($this) . " can't support push service.");
        }
    }
    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function getTopics($topic) {
        if (empty($this->topics[$topic])) {
            throw new Exception('topic "' + $topic + '" is not published.');
        }
        return $this->topics[$topic];
    }
    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function delTimer(ArrayObject $topics, $id) {
        $t = $topics[$id];
        if (isset($t->timer)) {
            $this->timer->clearTimeout($t->timer);
            unset($t->timer);
        }
    }
    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function offline(ArrayObject $topics, $topic, $id) {
        $this->delTimer($topics, $id);
        $messages = $topics[$id]->messages;
        unset($topics[$id]);
        foreach ($messages as $message) {
            $message->detector->resolve(false);
        }
        $onUnsubscribe = $this->onUnsubscribe;
        if (is_callable($onUnsubscribe)) {
            call_user_func($onUnsubscribe, $topic, $id, $this);
        }
    }
    private function setTimer(ArrayObject $topics, $topic, $id) {
        $t = $topics[$id];
        if (!isset($t->timer)) {
            $self = $this;
            $t->timer = $this->timer->setTimeout(function()
                    use ($self, $topics, $topic, $id) {
                $self->offline($topics, $topic, $id);
            }, $t->heartbeat);
        }
    }
    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function resetTimer(ArrayObject $topics, $topic, $id) {
        $this->delTimer($topics, $id);
        $this->setTimer($topics, $topic, $id);
    }
    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function setRequestTimer($topic, $id, $request, $timeout) {
        if ($timeout > 0) {
            $self = $this;
            $topics = $this->getTopics($topic);
            $future = new Future();
            $timer = $this->timer->setTimeout(function() use ($future) {
                $future->reject(new TimeoutException('timeout'));
            }, $timeout);
            $request->whenComplete(function() use ($self, $timer) {
                $self->timer->clearTimeout($timer);
            })->fill($future);
            return $future->catchError(function($e) use ($self, $topics, $topic, $id) {
                if ($e instanceof TimeoutException) {
                    $checkoffline = function() use ($self, &$checkoffline, $topics, $topic, $id) {
                        $t = $topics[$id];
                        $t->timer = $self->timer->setTimeout($checkoffline, $t->heartbeat);
                        if ($t->count < 0) {
                            $self->offline($topics, $topic, $id);
                        }
                        else {
                            --$t->count;
                        }
                    };
                    $checkoffline();
                }
            });
        }
        return $request;
    }
    public function publish($topic, array $options = array()) {
        $this->checkPushService();
        if (is_array($topic)) {
            foreach ($topic as $t) {
                $this->publish($t, $options);
            }
            return $this;
        }
        $self = $this;
        $timeout = isset($options['timeout']) ? $options['timeout'] : $this->timeout;
        $heartbeat = isset($options['heartbeat']) ? $options['heartbeat'] : $this->heartbeat;
        $this->topics[$topic] = new ArrayObject();
        return $this->addFunction(function($id) use ($self, $topic, $timeout, $heartbeat) {
            $topics = $self->getTopics($topic);
            if (isset($topics[$id])) {
                if ($topics[$id]->count < 0) {
                    $topics[$id]->count = 0;
                }
                $messages = $topics[$id]->messages;
                if (!$messages->isEmpty()) {
                    $message = $messages->shift();
                    $message->detector->resolve(true);
                    $self->resetTimer($topics, $topic, $id);
                    return $message->result;
                }
                else {
                    $self->delTimer($topics, $id);
                    $topics[$id]->count++;
                }
            }
            else {
                $topics[$id] = new stdClass();
                $topics[$id]->messages = new SplQueue();
                $topics[$id]->count = 1;
                $topics[$id]->heartbeat = $heartbeat;
                $this->timer->setImmediate(function() use ($self, $topic, $id) {
                    $onSubscribe = $self->onSubscribe;
                    if (is_callable($onSubscribe)) {
                        call_user_func($onSubscribe, $topic, $id, $self);
                    }
                });
            }
            if (isset($topics[$id]->request)) {
                $topics[$id]->request->resolve(null);
            }
            $request = new Future();
            $request->complete(function() use ($topics, $id) {
                $topics[$id]->count--;
            });
            $topics[$id]->request = $request;
            return $self->setRequestTimer($topic, $id, $request, $timeout);
        }, $topic);
    }
    /*
        This method is a private method.
        But PHP 5.3 can't call private method in closure,
        so we comment the private keyword.
    */
    /*private*/ function internalPush($topic, $id, $result) {
        if (Future\isFuture($result)) {
            $self = $this;
            return $result->complete(function($value) use ($self, $topic, $id) {
                return $self->internalPush($topic, $id, $value);
            });
        }
        $topics = $this->getTopics($topic);
        if (!isset($topics[$id])) {
            return Future\value(false);
        }
        if (isset($topics[$id]->request)) {
            $topics[$id]->request->resolve($result);
            unset($topics[$id]->request);
            $this->setTimer($topics, $topic, $id);
            return Future\value(true);
        }
        else {
            $detector = new Future();
            $message = new stdClass();
            $message->detector = $detector;
            $message->result = $result;
            $topics[$id]->messages->push($message);
            $this->setTimer($topics, $topic, $id);
            return $detector;
        }
    }
    public function idlist($topic) {
        return array_keys($this->getTopics($topic)->getArrayCopy());
    }
    public function exist($topic, $id) {
        $topics = $this->getTopics($topic);
        return isset($topics[$id]);
    }
    public function broadcast($topic, $result, $callback = null) {
        $this->checkPushService();
        $this->multicast($topic, $this->idlist($topic), $result, $callback);
    }
    public function multicast($topic, $ids, $result, $callback = null) {
        $this->checkPushService();
        if (!is_callable($callback)) {
            foreach ($ids as $id) {
                $this->internalPush($topic, $id, $result);
            }
            return;
        }
        $sent = array();
        $unsent = array();
        $n = count($ids);
        $count = $n;
        $check = function($id) use (&$sent, &$unsent, &$count, $callback) {
            return function($success) use ($id, &$sent, &$unsent, &$count, $callback) {
                if ($success) {
                    $sent[] = $id;
                }
                else {
                    $unsent[] = $id;
                }
                if (--$count === 0) {
                    call_user_func($callback, $sent, $unsent);
                }
            };
        };
        for ($i = 0; $i < $n; ++$i) {
            $id = $ids[$i];
            if ($id !== null) {
                $this->internalPush($topic, $id, $result)->then($check($id));
            }
            else {
                --$count;
            }
        }
    }
    public function unicast($topic, $id, $result, $callback = null) {
        $this->checkPushService();
        $detector = $this->internalPush($topic, $id, $result);
        if (is_callable($callback)) {
            $detector->then($callback);
        }
    }
    // push($topic, $result)
    // push($topic, $ids, $result)
    // push($topic, $id, $result)
    public function push($topic) {
        $this->checkPushService();
        $args = func_get_args();
        $argc = func_num_args();
        $id = null;
        $result = null;
        if (($argc < 2) || ($argc > 3)) {
            throw new Exception('Wrong number of arguments');
        }
        if ($argc === 2) {
            $result = $args[1];
        }
        else {
            $id = $args[1];
            $result = $args[2];
        }
        if ($id === null) {
            $topics = $this->getTopics($topic);
            $iterator = $topics->getIterator();
            while($iterator->valid()) {
                $id = $iterator->key();
                $this->internalPush($topic, $id, $result);
                $iterator->next();
            }
        }
        elseif (is_array($id)) {
            $ids = $id;
            foreach ($ids as $id) {
                $this->internalPush($topic, $id, $result);
            }
        }
        else {
            $this->internalPush($topic, $id, $result);
        }
    }

    /**
     * @return array
     */
    public function getNames(){
        return $this->names;
    }
}
