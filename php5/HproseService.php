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
 * HproseService.php                                      *
 *                                                        *
 * hprose service library for php.                        *
 *                                                        *
 * LastModified: Mar 5, 2015                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

require_once('HproseCommon.php');
require_once('HproseIO.php');

class HproseRemoteCall {
    public $func;
    public $mode;
    public $simple;
    public function __construct($func, $mode, $simple) {
        $this->func = $func;
        $this->mode = $mode;
        $this->simple = $simple;
    }
}
abstract class HproseService {
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
    protected static $errorTable = array(
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
    private $calls = array();
    private $names = array();
    private $filters = array();
    private $simple = false;
    protected $debug = false;
    protected $error_types;
    public $onBeforeInvoke = null;
    public $onAfterInvoke = null;
    public $onSendError = null;

    public function __construct() {
        $this->error_types = E_ALL & ~E_NOTICE;
    }
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
    protected function sendError($error, $context) {
        if ($this->onSendError !== null) {
            $sendError = $this->onSendError;
            $sendError($error, $context);
        }
        $data = HproseTags::TagError .
                hprose_serialize_string($error) .
                HproseTags::TagEnd;
        return $this->outputFilter($data, $context);
    }
    protected function doInvoke($input, $context) {
        $output = new HproseStringStream();
        do {
            $name = hprose_unserialize_string_with_stream($input, true);
            $alias = strtolower($name);
            if (array_key_exists($alias, $this->calls)) {
                $call = $this->calls[$alias];
            }
            elseif (array_key_exists('*', $this->calls)) {
                $call = $this->calls['*'];
            }
            else {
                throw new Exception("Can't find this function " . $name . "().");
            }
            $mode = $call->mode;
            $simple = $call->simple;
            if ($simple === null) {
                $simple = $this->simple;
            }
            $args = array();
            $byref = false;
            $tag = $input->getc();
            if ($tag == HproseTags::TagList) {
                $args = hprose_unserialize_list_with_stream($input);
                $tag = $input->getc();
                if ($tag == HproseTags::TagTrue) {
                    $byref = true;
                    $tag = $input->getc();
                    $_args = array();
                    foreach($args as &$arg) $_args[] = &$arg;
                    $args = $_args;
                }
            }
            if (($tag != HproseTags::TagEnd) && ($tag != HproseTags::TagCall)) {
                throw new Exception('Unknown tag: ' . $tag . "\r\n" .
                                     'with following data: ' . $input->toString());
            }
            if ($this->onBeforeInvoke !== null) {
                $beforeInvoke = $this->onBeforeInvoke;
                $beforeInvoke($name, $args, $byref, $context);
            }
            if (array_key_exists('*', $this->calls) &&
                $call === $this->calls['*']) {
                $args = array($name, $args);
            }
            $result = call_user_func_array($call->func, $args);
            if ($this->onAfterInvoke !== null) {
                $afterInvoke = $this->onAfterInvoke;
                $afterInvoke($name, $args, $byref, $result, $context);
            }
            // some service functions/methods may echo content, we need clean it
            @ob_clean();
            if ($mode == HproseResultMode::RawWithEndTag) {
                return $this->outputFilter($result, $context);
            }
            elseif ($mode == HproseResultMode::Raw) {
                $output->write($result);
            }
            else {
                $output->write(HproseTags::TagResult);
                if ($mode == HproseResultMode::Serialized) {
                    $output->write($result);
                }
                else {
                    $output->write(hprose_serialize($result, $simple));
                }
                if ($byref) {
                    $output->write(HproseTags::TagArgument .
                                   hprose_serialize_list($args, $simple));
                }
            }
        } while ($tag == HproseTags::TagCall);
        $output->write(HproseTags::TagEnd);
        return $this->outputFilter($output->toString(), $context);
    }
    protected function doFunctionList($context) {
        $data = HproseTags::TagFunctions .
                hprose_serialize_list($this->names, true) .
                HproseTags::TagEnd;
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
                                       $mode = HproseResultMode::Normal,
                                       $simple = null) {
        $this->addFunction($func, '*', $mode, $simple);
    }
    public function addFunction($func,
                                $alias = '',
                                $mode = HproseResultMode::Normal,
                                $simple = null) {
        if (!is_callable($func)) {
            throw new Exception('Argument func is not callable.');
        }
        if ($alias == '') {
            if (is_string($func)) {
                $alias = $func;
            }
            elseif (is_array($func)) {
                $alias = $func[1];
            }
            else {
                throw new Exception('alias must be a string.');
            }
        }
        $name = strtolower($alias);
        if (!array_key_exists($name, $this->calls)) {
            $this->names[] = $alias;
        }
        $this->calls[$name] = new HproseRemoteCall($func, $mode, $simple);
    }
    public function addFunctions($funcs,
                                 $aliases = array(),
                                 $mode = HproseResultMode::Normal,
                                 $simple = null) {
        $count = count($aliases);
        if ($count == 0) {
            foreach ($funcs as $func) {
                $this->addFunction($func, '', $mode, $simple);
            }
        }
        elseif ($count == count($funcs)) {
            foreach ($funcs as $i => $func) {
                $this->addFunction($func, $aliases[$i], $mode, $simple);
            }
        }
        else {
            throw new Exception('The count of functions is not matched with aliases');
        }
    }
    public function addMethod($methodname,
                              $belongto,
                              $alias = '',
                              $mode = HproseResultMode::Normal,
                              $simple = null) {
        $func = array($belongto, $methodname);
        $this->addFunction($func, $alias, $mode, $simple);
    }
    public function addMethods($methods,
                               $belongto,
                               $aliases = '',
                               $mode = HproseResultMode::Normal,
                               $simple = null) {
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
            throw new Exception('The count of methods is not matched with aliases');
        }
        foreach($methods as $k => $method) {
            $func = array($belongto, $method);
            $this->addFunction($func, $_aliases[$k], $mode, $simple);
        }
    }
    public function addInstanceMethods($object,
                                       $class = '',
                                       $aliasPrefix = '',
                                       $mode = HproseResultMode::Normal,
                                       $simple = null) {
        if ($class == '') {
            $class = get_class($object);
        }
        $this->addMethods(self::getDeclaredOnlyMethods($class),
                          $object, $aliasPrefix, $mode, $simple);
    }
    public function addClassMethods($class,
                                    $execclass = '',
                                    $aliasPrefix = '',
                                    $mode = HproseResultMode::Normal,
                                    $simple = null) {
        if ($execclass == '') {
            $execclass = $class;
        }
        $this->addMethods(self::getDeclaredOnlyMethods($class),
                          $execclass, $aliasPrefix, $mode, $simple);
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
                    if (is_string($args[1]) && !is_callable(array($args[0], $args[1]))) {
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
            throw new Exception('Wrong arguments');
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
        $input = new HproseStringStream($this->inputFilter($request, $context));
        try {
            switch ($input->getc()) {
                case HproseTags::TagCall:
                    return $this->doInvoke($input, $context);
                case HproseTags::TagEnd:
                    return $this->doFunctionList($context);
                default:
                    throw new Exception("Wrong Request: \r\n" . $request);
            }
        }
        catch (Exception $e) {
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
