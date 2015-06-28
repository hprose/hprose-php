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
 * LastModified: Jun 28, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose {

    define('HPROSE_DEFAULT_ERROR_TYPES', E_ALL & ~E_NOTICE);

    class RemoteCall {
        public $func;
        public $mode;
        public $simple;
        public $params;
        public $byref;
        public $async;
        public function __construct($func, $mode, $simple, $async) {
            $this->func = $func;
            $this->mode = $mode;
            $this->simple = $simple;
            $this->async = $async;
            if (is_array($func)) {
                $tmp = new \ReflectionMethod($func[0], $func[1]);
            }
            else {
                $tmp = new \ReflectionFunction($func);
            }
            $this->params = $tmp->getParameters();
            $this->byref = false;
            foreach($this->params as $param) {
                if ($param->isPassedByReference()) {
                    $this->byref = true;
                    break;
                }
            }
        }
    }

    class AsyncCallback {
        private $completer;
        public function __construct($completer) {
            $this->completer = $completer;
        }
        public function handler($result) {
            if ($result instanceof \Exception) {
                $this->completer->completeError($result);
            }
            else {
                $this->completer->complete($result);
            }
        }
    }

    class AfterInvokeCallback {
        private $service;
        private $completer;
        private $name;
        private $args;
        private $byref;
        private $mode;
        private $simple;
        private $context;
        public function __construct($service, $completer, $name, $args, $byref, $mode, $simple, $context) {
            $this->service = $service;
            $this->completer = $completer;
            $this->name = $name;
            $this->args = $args;
            $this->byref = $byref;
            $this->mode = $mode;
            $this->simple = $simple;
            $this->context = $context;
        }
        public function handler($result) {
            if ($result instanceof \Exception) {
                $this->errorHandler($result);
            }
            else {
                try {
                    $result = $this->service->afterInvoke(
                        $this->name,
                        $this->args,
                        $this->byref,
                        $this->mode,
                        $this->simple,
                        $this->context,
                        $result,
                        new BytesIO(),
                        true);
                    $this->completer->complete($result);
                }
                catch (\Exception $e) {
                    $this->errorHandler($e);
                }
            }
        }
        public function errorHandler($e) {
            $error = $e->getMessage();
            if ($this->service->isDebugEnabled()) {
                $error .= "\nfile: " . $e->getFile() .
                          "\nline: " . $e->getLine() .
                          "\ntrace: " . $e->getTraceAsString();
            }
            $this->completer->complete(
                $this->service->sendError(
                    $error,
                    $this->context));
        }
    }

    abstract class Service {
        private static $magic_methods = array(
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
        private static $errorTable = array(
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_DEPRECATED => 'Deprecated',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_USER_DEPRECATED => 'User Deprecated',
            E_STRICT => 'Runtime Notice',
            E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
        );
        public static function getErrorTypeString($errno) {
            return self::$errorTable[$errno];
        }
        private $calls = array();
        private $names = array();
        private $filters = array();
        private $simple = false;
        protected $debug = false;
        protected $error_types = HPROSE_DEFAULT_ERROR_TYPES;
        public $onBeforeInvoke = null;
        public $onAfterInvoke = null;
        public $onSendError = null;

        private function inputFilter($data, $context) {
            $count = count($this->filters);
            for ($i = $count - 1; $i >= 0; $i--) {
                $data = $this->filters[$i]->inputFilter($data, $context);
            }
            return $data;
        }
        private function outputFilter($data, $context) {
            $count = count($this->filters);
            for ($i = 0; $i < $count; $i++) {
                $data = $this->filters[$i]->outputFilter($data, $context);
            }
            return $data;
        }
        public function sendError($error, $context) {
            if ($this->onSendError !== null) {
                $sendError = $this->onSendError;
                call_user_func_array($sendError, array(&$error, $context));
            }
            $stream = new BytesIO();
            $writer = new Writer($stream, true);
            $stream->write(Tags::TagError);
            $writer->writeString($error);
            $stream->write(Tags::TagEnd);
            $data = $stream->toString();
            $stream->close();
            return $this->outputFilter($data, $context);
        }
        // this method is public only for async callback
        public function afterInvoke($name, $args, $byref, $mode, $simple, $context, $result, $output, $async) {
            if ($this->onAfterInvoke !== null) {
                $afterInvoke = $this->onAfterInvoke;
                call_user_func_array($afterInvoke, array($name, &$args, $byref, &$result, $context));
            }
            if ($mode == ResultMode::RawWithEndTag) {
                return $this->outputFilter($result, $context);
            }
            elseif ($mode == ResultMode::Raw) {
                $output->write($result);
            }
            else {
                $writer = new Writer($output, $simple);
                $output->write(Tags::TagResult);
                if ($mode == ResultMode::Serialized) {
                    $output->write($result);
                }
                else {
                    $writer->reset();
                    $writer->serialize($result);
                }
                if ($byref) {
                    $output->write(Tags::TagArgument);
                    $writer->reset();
                    $writer->writeArray($args);
                }
            }
            if ($async) {
                $output->write(Tags::TagEnd);
                return $this->outputFilter($output->toString(), $context);
            }
            return null;
        }
        protected function doInvoke($input, $context) {
            $output = new BytesIO();
            $reader = new Reader($input);
            do {
                $reader->reset();
                $name = $reader->readString();
                $alias = strtolower($name);
                if (isset($this->calls[$alias])) {
                    $call = $this->calls[$alias];
                }
                elseif (isset($this->calls['*'])) {
                    $call = $this->calls['*'];
                }
                else {
                    throw new \Exception("Can't find this function " . $name . "().");
                }
                $mode = $call->mode;
                $simple = $call->simple;
                if ($simple === null) {
                    $simple = $this->simple;
                }
                $args = array();
                $byref = false;
                $async = $call->async;
                $tag = $input->getc();
                if ($tag == Tags::TagList) {
                    $reader->reset();
                    $args = $reader->readListWithoutTag();
                    $tag = $input->getc();
                    if ($tag == Tags::TagTrue) {
                        $byref = true;
                        $tag = $input->getc();
                    }
                    if ($call->byref) {
                        $_args = array();
                        foreach($args as $i => &$arg) {
                            if ($call->params[$i]->isPassedByReference()) {
                                $_args[] = &$arg;
                            }
                            else {
                                $_args[] = $arg;
                            }
                        }
                        $args = $_args;
                    }
                }
                if (($tag != Tags::TagEnd) && ($tag != Tags::TagCall)) {
                    throw new \Exception('Unknown tag: ' . $tag . "\r\n" .
                                         'with following data: ' . $input->toString());
                }
                if ($this->onBeforeInvoke !== null) {
                    $beforeInvoke = $this->onBeforeInvoke;
                    call_user_func_array($beforeInvoke, array($name, &$args, $byref, $context));
                }
                if (array_key_exists('*', $this->calls) &&
                    $call === $this->calls['*']) {
                    $args = array($name, $args);
                }
                if ($async) {
                    $completer = new Completer();
                    $args[] = array(new AsyncCallback($completer), "handler");
                    call_user_func_array($call->func, $args);
                    $result = $completer->future();
                }
                else {
                    $result = call_user_func_array($call->func, $args);
                }
                if ($result != null && $result instanceof Future) {
                    $completer = new Completer();
                    $callback = new AfterInvokeCallback($this, $completer, $name, $args, $byref, $mode, $simple, $context);
                    $result->then(array($callback, "handler"))->catchError(array($callback, "errorHandler"));
                    return $completer->future();
                }
                $result = $this->afterInvoke($name, $args, $byref, $mode, $simple, $context, $result, $output, false);
                if ($result != null) return $result;
            } while ($tag == Tags::TagCall);
            $output->write(Tags::TagEnd);
            return $this->outputFilter($output->toString(), $context);
        }
        protected function doFunctionList($context) {
            $stream = new BytesIO();
            $writer = new Writer($stream, true);
            $stream->write(Tags::TagFunctions);
            $writer->writeArray($this->names);
            $stream->write(Tags::TagEnd);
            $data = $stream->toString();
            $stream->close();
            return $this->outputFilter($data, $context);
        }
        private static function getDeclaredOnlyMethods($class) {
            $result = get_class_methods($class);
            if ($parent_class = get_parent_class($class)) {
                $inherit = get_class_methods($parent_class);
                $result = array_diff($result, $inherit);
            }
            $result = array_diff($result, self::$magic_methods);
            return $result;
        }
        public function addMissingFunction($func,
                                           $mode = ResultMode::Normal,
                                           $simple = null,
                                           $async = false) {
            $this->addFunction($func, '*', $mode, $simple, $async);
        }
        public function addAsyncMissingFunction($func,
                                                $mode = ResultMode::Normal,
                                                $simple = null) {
            $this->addMissingFunction($func, $mode, $simple, true);
        }
        public function addFunction($func,
                                    $alias = '',
                                    $mode = ResultMode::Normal,
                                    $simple = null,
                                    $async = false) {
            if (!is_callable($func)) {
                throw new \Exception('Argument func is not callable.');
            }
            if ($alias == '') {
                if (is_string($func)) {
                    $alias = $func;
                }
                elseif (is_array($func)) {
                    $alias = $func[1];
                }
                else {
                    throw new \Exception('alias must be a string.');
                }
            }
            $name = strtolower($alias);
            if (!array_key_exists($name, $this->calls)) {
                $this->names[] = $alias;
            }
            $this->calls[$name] = new RemoteCall($func, $mode, $simple, $async);
        }
        public function addAsyncFunction($func,
                                         $alias = '',
                                         $mode = ResultMode::Normal,
                                         $simple = null) {
            $this->addFunction($func, $alias, $mode, $simple, true);
        }
        public function addFunctions($funcs,
                                     $aliases = array(),
                                     $mode = ResultMode::Normal,
                                     $simple = null,
                                     $async = false) {
            $count = count($aliases);
            if ($count == 0) {
                foreach ($funcs as $func) {
                    $this->addFunction($func, '', $mode, $simple, $async);
                }
            }
            elseif ($count == count($funcs)) {
                foreach ($funcs as $i => $func) {
                    $this->addFunction($func, $aliases[$i], $mode, $simple, $async);
                }
            }
            else {
                throw new \Exception('The count of functions is not matched with aliases');
            }
        }
        public function addAsyncFunctions($funcs,
                                          $aliases = array(),
                                          $mode = ResultMode::Normal,
                                          $simple = null) {
            $this->addFunctions($funcs, $aliases, $mode, $simple, true);
        }
        public function addMethod($methodname,
                                  $belongto,
                                  $alias = '',
                                  $mode = ResultMode::Normal,
                                  $simple = null,
                                  $async = false) {
            $func = array($belongto, $methodname);
            $this->addFunction($func, $alias, $mode, $simple, $async);
        }
        public function addAsyncMethod($methodname,
                                       $belongto,
                                       $alias = '',
                                       $mode = ResultMode::Normal,
                                       $simple = null) {
            $this->addMethod($methodname, $belongto, $alias, $mode, $simple, true);
        }
        public function addMethods($methods,
                                   $belongto,
                                   $aliases = '',
                                   $mode = ResultMode::Normal,
                                   $simple = null,
                                   $async = false) {
            if ($aliases === null || count($aliases) == 0) {
                $aliases = '';
            }
            $_aliases = array();
            if (is_string($aliases)) {
                $aliasPrefix = $aliases;
                if ($aliasPrefix !== '') {
                    $aliasPrefix .= '_';
                }
                foreach ($methods as $k => $method) {
                    $_aliases[$k] = $aliasPrefix . $method;
                }
            }
            elseif (is_array($aliases)) {
                $_aliases = $aliases;
            }
            if (count($methods) != count($_aliases)) {
                throw new \Exception('The count of methods is not matched with aliases');
            }
            foreach($methods as $k => $method) {
                $func = array($belongto, $method);
                $this->addFunction($func, $_aliases[$k], $mode, $simple, $async);
            }
        }
        public function addAsyncMethods($methods,
                                        $belongto,
                                        $aliases = '',
                                        $mode = ResultMode::Normal,
                                        $simple = null) {
            $this->addMethods($methods, $belongto, $aliases, $mode, $simple, true);
        }
        public function addInstanceMethods($object,
                                           $class = '',
                                           $aliasPrefix = '',
                                           $mode = ResultMode::Normal,
                                           $simple = null,
                                           $async = false) {
            if ($class == '') {
                $class = get_class($object);
            }
            $this->addMethods(self::getDeclaredOnlyMethods($class),
                              $object, $aliasPrefix, $mode, $simple, $async);
        }
        public function addAsyncInstanceMethods($object,
                                                $class = '',
                                                $aliasPrefix = '',
                                                $mode = ResultMode::Normal,
                                                $simple = null) {
            $this->addInstanceMethods($object, $class, $aliasPrefix, $mode, $simple, true);
        }
        public function addClassMethods($class,
                                        $execclass = '',
                                        $aliasPrefix = '',
                                        $mode = ResultMode::Normal,
                                        $simple = null,
                                        $async = false) {
            if ($execclass == '') {
                $execclass = $class;
            }
            $this->addMethods(self::getDeclaredOnlyMethods($class),
                              $execclass, $aliasPrefix, $mode, $simple, $async);
        }
        public function addAsyncClassMethods($class,
                                             $execclass = '',
                                             $aliasPrefix = '',
                                             $mode = ResultMode::Normal,
                                             $simple = null) {
            $this->addClassMethods($class, $execclass, $aliasPrefix, $mode, $simple, true);
        }
        public function add() {
            $args_num = func_num_args();
            $args = func_get_args();
            switch ($args_num) {
                case 1: {
                    if (is_callable($args[0])) {
                        $this->addFunction($args[0]);
                        return;
                    }
                    elseif (is_array($args[0])) {
                        $this->addFunctions($args[0]);
                        return;
                    }
                    elseif (is_object($args[0])) {
                        $this->addInstanceMethods($args[0]);
                        return;
                    }
                    elseif (is_string($args[0])) {
                        $this->addClassMethods($args[0]);
                        return;
                    }
                    break;
                }
                case 2: {
                    if (is_callable($args[0]) && is_string($args[1])) {
                        $this->addFunction($args[0], $args[1]);
                        return;
                    }
                    elseif (is_string($args[0])) {
                        if (is_string($args[1]) && !is_callable(array($args[1], $args[0]))) {
                            if (class_exists($args[1])) {
                                $this->addClassMethods($args[0], $args[1]);
                            }
                            else {
                                $this->addClassMethods($args[0], '', $args[1]);
                            }
                        }
                        else {
                            $this->addMethod($args[0], $args[1]);
                        }
                        return;
                    }
                    elseif (is_array($args[0])) {
                        if (is_array($args[1])) {
                            $this->addFunctions($args[0], $args[1]);
                        }
                        else {
                            $this->addMethods($args[0], $args[1]);
                        }
                        return;
                    }
                    elseif (is_object($args[0])) {
                        $this->addInstanceMethods($args[0], $args[1]);
                        return;
                    }
                    break;
                }
                case 3: {
                    if (is_callable($args[0]) && $args[1] == '' && is_string($args[2])) {
                        $this->addFunction($args[0], $args[2]);
                        return;
                    }
                    elseif (is_string($args[0]) && is_string($args[2])) {
                        if (is_string($args[1]) && !is_callable(array($args[1], $args[0]))) {
                            $this->addClassMethods($args[0], $args[1], $args[2]);
                        }
                        else {
                            $this->addMethod($args[0], $args[1], $args[2]);
                        }
                        return;
                    }
                    elseif (is_array($args[0])) {
                        if ($args[1] == '' && is_array($args[2])) {
                            $this->addFunctions($args[0], $args[2]);
                        }
                        else {
                            $this->addMethods($args[0], $args[1], $args[2]);
                        }
                        return;
                    }
                    elseif (is_object($args[0])) {
                        $this->addInstanceMethods($args[0], $args[1], $args[2]);
                        return;
                    }
                    break;
                }
                throw new \Exception('Wrong arguments');
            }
        }
        public function addAsync() {
            $args_num = func_num_args();
            $args = func_get_args();
            switch ($args_num) {
                case 1: {
                    if (is_callable($args[0])) {
                        $this->addAsyncFunction($args[0]);
                        return;
                    }
                    elseif (is_array($args[0])) {
                        $this->addAsyncFunctions($args[0]);
                        return;
                    }
                    elseif (is_object($args[0])) {
                        $this->addAsyncInstanceMethods($args[0]);
                        return;
                    }
                    elseif (is_string($args[0])) {
                        $this->addAsyncClassMethods($args[0]);
                        return;
                    }
                    break;
                }
                case 2: {
                    if (is_callable($args[0]) && is_string($args[1])) {
                        $this->addAsyncFunction($args[0], $args[1]);
                        return;
                    }
                    elseif (is_string($args[0])) {
                        if (is_string($args[1]) && !is_callable(array($args[1], $args[0]))) {
                            if (class_exists($args[1])) {
                                $this->addAsyncClassMethods($args[0], $args[1]);
                            }
                            else {
                                $this->addAsyncClassMethods($args[0], '', $args[1]);
                            }
                        }
                        else {
                            $this->addAsyncMethod($args[0], $args[1]);
                        }
                        return;
                    }
                    elseif (is_array($args[0])) {
                        if (is_array($args[1])) {
                            $this->addAsyncFunctions($args[0], $args[1]);
                        }
                        else {
                            $this->addAsyncMethods($args[0], $args[1]);
                        }
                        return;
                    }
                    elseif (is_object($args[0])) {
                        $this->addAsyncInstanceMethods($args[0], $args[1]);
                        return;
                    }
                    break;
                }
                case 3: {
                    if (is_callable($args[0]) && $args[1] == '' && is_string($args[2])) {
                        $this->addAsyncFunction($args[0], $args[2]);
                        return;
                    }
                    elseif (is_string($args[0]) && is_string($args[2])) {
                        if (is_string($args[1]) && !is_callable(array($args[1], $args[0]))) {
                            $this->addAsyncClassMethods($args[0], $args[1], $args[2]);
                        }
                        else {
                            $this->addAsyncMethod($args[0], $args[1], $args[2]);
                        }
                        return;
                    }
                    elseif (is_array($args[0])) {
                        if ($args[1] == '' && is_array($args[2])) {
                            $this->addAsyncFunctions($args[0], $args[2]);
                        }
                        else {
                            $this->addAsyncMethods($args[0], $args[1], $args[2]);
                        }
                        return;
                    }
                    elseif (is_object($args[0])) {
                        $this->addAsyncInstanceMethods($args[0], $args[1], $args[2]);
                        return;
                    }
                    break;
                }
                throw new \Exception('Wrong arguments');
            }
        }
        public function isDebugEnabled() {
            return $this->debug;
        }
        public function setDebugEnabled($enable = true) {
            $this->debug = $enable;
        }
        public function getFilter() {
            if (count($this->filters) === 0) {
                return null;
            }
            return $this->filters[0];
        }
        public function setFilter($filter) {
            $this->filters = array();
            if ($filter !== null) {
                $this->filters[] = $filter;
            }
        }
        public function addFilter($filter) {
            $this->filters[] = $filter;
        }
        public function removeFilter($filter) {
            $i = array_search($filter, $this->filters);
            if ($i === false || $i === null) {
                return false;
            }
            $this->filters = array_splice($this->filters, $i, 1);
            return true;
        }
        public function getSimpleMode() {
            return $this->simple;
        }
        public function setSimpleMode($simple = true) {
            $this->simple = $simple;
        }
        public function getErrorTypes() {
            return $this->error_types;
        }
        public function setErrorTypes($error_types) {
            $this->error_types = $error_types;
        }
        public function defaultHandle($request, $context) {
            try {
                $input = new BytesIO($this->inputFilter($request, $context));
                switch ($input->getc()) {
                    case Tags::TagCall:
                        return $this->doInvoke($input, $context);
                    case Tags::TagEnd:
                        return $this->doFunctionList($context);
                    default:
                        throw new \Exception("Wrong Request: \r\n" . $request);
                }
            }
            catch (\Exception $e) {
                $error = $e->getMessage();
                if ($this->debug) {
                    $error .= "\nfile: " . $e->getFile() .
                              "\nline: " . $e->getLine() .
                              "\ntrace: " . $e->getTraceAsString();
                }
                return $this->sendError($error, $context);
            }
        }
    }
}
