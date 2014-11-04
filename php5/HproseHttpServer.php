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
 * HproseHttpServer.php                                   *
 *                                                        *
 * hprose http server library for php5.                   *
 *                                                        *
 * LastModified: Nov 4, 2014                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

require_once('HproseCommon.php');
require_once('HproseIO.php');

defined('E_DEPRECATED') or define('E_DEPRECATED', 8192);
defined('E_USER_DEPRECATED') or define('E_USER_DEPRECATED', 16384);

class HproseHttpServer {
    private $magic_methods = array("__construct",
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
                                   "__clone");
    private $errorTable = array(E_ERROR => 'Error',
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
                                E_STRICT => 'Run-time Notice',
                                E_RECOVERABLE_ERROR => 'Error');
    private $functions;
    private $funcNames;
    private $resultModes;
    private $simpleModes;
    private $debug;
    private $crossDomain;
    private $P3P;
    private $get;
    private $input;
    private $output;
    private $error;
    private $filters;
    private $origins;
    private $simple;
    private $context;
    public $onBeforeInvoke;
    public $onAfterInvoke;
    public $onSendHeader;
    public $onSendError;
    public function __construct() {
        $this->functions = array();
        $this->funcNames = array();
        $this->resultModes = array();
        $this->simpleModes = array();
        $this->debug = false;
        $this->crossDomain = false;
        $this->P3P = false;
        $this->get = true;
        $this->filters = array();
        $this->origins = array();
        $this->simple = false;
        $this->error_types = E_ALL & ~E_NOTICE;
        $this->context = new stdClass();
        $this->context->server = $this;
        $this->context->userdata = new stdClass();
        $this->onBeforeInvoke = NULL;
        $this->onAfterInvoke = NULL;
        $this->onSendHeader = NULL;
        $this->onSendError = NULL;
    }
    private function inputFilter($data) {
        $count = count($this->filters);
        for ($i = $count - 1; $i >= 0; $i--) {
            $data = $this->filters[$i]->inputFilter($data, $this->context);
        }
        return $data;
    }
    private function outputFilter($data) {
        $count = count($this->filters);
        for ($i = 0; $i < $count; $i++) {
            $data = $this->filters[$i]->outputFilter($data, $this->context);
        }
        return $data;
    }
    /*
      __filterHandler & __errorHandler must be public,
      however we should never call them directly.
    */
    public function __filterHandler($data) {
        if (preg_match('/<b>.*? error<\/b>:(.*?)<br/', $data, $match)) {
            if ($this->debug) {
                $error = preg_replace('/<.*?>/', '', $match[1]);
            }
            else {
                $error = preg_replace('/ in <b>.*<\/b>$/', '', $match[1]);
            }
            $data = HproseTags::TagError .
                 hprose_serialize_string(trim($error)) .
                 HproseTags::TagEnd;
        }
        return $this->outputFilter($data);
    }
    public function __errorHandler($errno, $errstr, $errfile, $errline) {
        if ($this->debug) {
            $errstr .= " in $errfile on line $errline";
        }
        $this->error = $this->errorTable[$errno] . ": " . $errstr;
        $this->sendError();
        exit();
    }
    private function sendHeader() {
        if ($this->onSendHeader) {
            call_user_func($this->onSendHeader, $this->context);
        }
        header("Content-Type: text/plain");
        if ($this->P3P) {
            header('P3P: CP="CAO DSP COR CUR ADM DEV TAI PSA PSD IVAi IVDi ' .
                   'CONi TELo OTPi OUR DELi SAMi OTRi UNRi PUBi IND PHY ONL ' .
                   'UNI PUR FIN COM NAV INT DEM CNT STA POL HEA PRE GOV"');
        }
        if ($this->crossDomain) {
            if (array_key_exists('HTTP_ORIGIN', $_SERVER) && $_SERVER['HTTP_ORIGIN'] != "null") {
                if (count($this->origins) === 0 || array_key_exists($_SERVER['HTTP_ORIGIN'], $this->origins)) {
                    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
                    header("Access-Control-Allow-Credentials: true");
                }
            }
            else {
                header('Access-Control-Allow-Origin: *');
            }
        }
    }
    private function responseEnd() {
        @ob_end_clean();
        print($this->outputFilter($this->output->toString()));
    }
    private function sendError() {
        if ($this->onSendError) {
            call_user_func($this->onSendError, $this->error, $this->context);
        }
        @ob_clean();
        $this->output->write(HproseTags::TagError .
                             hprose_serialize_string($this->error) .
                             HproseTags::TagEnd);
        $this->responseEnd();
    }
    private function doInvoke() {
        // $simpleReader = new HproseReader($this->input, true);
        do {
            $functionName = hprose_unserialize_with_stream($this->input, true);
            // $functionName = $simpleReader->readString();
            $aliasName = strtolower($functionName);
            $resultMode = HproseResultMode::Normal;
            if (array_key_exists($aliasName, $this->functions)) {
                $function = $this->functions[$aliasName];
                $resultMode = $this->resultModes[$aliasName];
                $simple = $this->simpleModes[$aliasName];
            }
            elseif (array_key_exists('*', $this->functions)) {
                $function = $this->functions['*'];
                $resultMode = $this->resultModes['*'];
                $simple = $this->simpleModes['*'];
            }
            else {
                throw new Exception("Can't find this function " . $functionName . "().");
            }
            if ($simple === NULL) $simple = $this->simple;
            // $writer = new HproseWriter($this->output, $simple);
            $args = array();
            $byref = false;
            // $tag = $simpleReader->checkTags(array(HproseTags::TagList,
            //                                 HproseTags::TagEnd,
            //                                 HproseTags::TagCall));
            $tag = $this->input->getc();
            if ($tag == HproseTags::TagList) {
                // $reader = new HproseReader($this->input);
                // $args = &$reader->readListWithoutTag();
                $args = &hprose_unserialize_list_with_stream($this->input);
                $tag = $this->input->getc();
                // $tag = $reader->checkTags(array(HproseTags::TagTrue,
                //                                 HproseTags::TagEnd,
                //                                 HproseTags::TagCall));
                if ($tag == HproseTags::TagTrue) {
                    $byref = true;
                    $tag = $this->input->getc();
                    // $tag = $reader->checkTags(array(HproseTags::TagEnd,
                    //                                 HproseTags::TagCall));
                }
            }
            if (($tag != HproseTags::TagEnd) && ($tag != HproseTags::TagCall)) {
                throw new Exception($tag);
                //throw new Exception("Wrong Request: \r\n" . $GLOBALS['HTTP_RAW_POST_DATA']);
            }
            if ($this->onBeforeInvoke) {
                call_user_func($this->onBeforeInvoke, $functionName, $args, $byref, $this->context);
            }
            if (array_key_exists('*', $this->functions) && ($function === $this->functions['*'])) {
                $arguments = array($functionName, &$args);
            }
            elseif ($byref) {
                $arguments = array();
                for ($i = 0; $i < count($args); $i++) {
                    $arguments[$i] = &$args[$i];
                }
            }
            else {
                $arguments = $args;
            }
            $result = call_user_func_array($function, $arguments);
            if ($this->onAfterInvoke) {
                call_user_func($this->onAfterInvoke, $functionName, $args, $byref, $result, $this->context);
            }
            // some service functions/methods may echo content, we need clean it
            @ob_clean();
            if ($resultMode == HproseResultMode::RawWithEndTag) {
                $this->output->write($result);
                return;
            }
            elseif ($resultMode == HproseResultMode::Raw) {
                $this->output->write($result);
            }
            else {
                $this->output->write(HproseTags::TagResult);
                if ($resultMode == HproseResultMode::Serialized) {
                    $this->output->write($result);
                }
                else {
                    // $writer->reset();
                    // $writer->serialize($result);
                    $this->output->write(hprose_serialize($result, $simple));
                }
                if ($byref) {
                    $this->output->write(HproseTags::TagArgument .
                                         hprose_serialize_list($args, $simple));
                    // $writer->reset();
                    // $writer->writeList($args);
                }
            }
        } while ($tag == HproseTags::TagCall);
        $this->output->write(HproseTags::TagEnd);
        $this->responseEnd();
    }
    private function doFunctionList() {
        $functions = array_values($this->funcNames);
        $this->output->write(HproseTags::TagFunctions .
                             hprose_serialize_list($functions, true) .
                             HproseTags::TagEnd);
        $this->responseEnd();
    }
    private function getDeclaredOnlyMethods($class) {
        $all = get_class_methods($class);
        if ($parent_class = get_parent_class($class)) {
            $inherit = get_class_methods($parent_class);
            $result = array_diff($all, $inherit);
        }
        else {
            $result = $all;
        }
        $result = array_diff($result, $this->magic_methods);
        return $result;
    }
    public function addMissingFunction($function, $resultMode = HproseResultMode::Normal, $simple = NULL) {
        $this->addFunction($function, '*', $resultMode, $simple);
    }
    public function addFunction($function, $alias = NULL, $resultMode = HproseResultMode::Normal, $simple = NULL) {
        if (is_callable($function)) {
            if ($alias === NULL) {
                if (is_string($function)) {
                    $alias = $function;
                }
                else {
                    $alias = $function[1];
                }
            }
            if (is_string($alias)) {
                $aliasName = strtolower($alias);
                $this->functions[$aliasName] = $function;
                $this->funcNames[$aliasName] = $alias;
                $this->resultModes[$aliasName] = $resultMode;
                $this->simpleModes[$aliasName] = $simple;
            }
            else {
                throw new Exception('Argument alias is not a string');
            }
        }
        else {
            throw new Exception('Argument function is not a callable variable');
        }
    }
    public function addFunctions($functions, $aliases = NULL, $resultMode = HproseResultMode::Normal, $simple = NULL) {
        $aliases_is_null = ($aliases === NULL);
        $count = count($functions);
        if (!$aliases_is_null && $count != count($aliases)) {
            throw new Exception('The count of functions is not matched with aliases');
        }
        for ($i = 0; $i < $count; $i++) {
            $function = $functions[$i];
            if ($aliases_is_null) {
                $this->addFunction($function, NULL, $resultMode, $simple);
            }
            else {
                $this->addFunction($function, $aliases[$i], $resultMode, $simple);
            }
        }
    }
    public function addMethod($methodname, $belongto, $alias = NULL, $resultMode = HproseResultMode::Normal, $simple = NULL) {
        if ($alias === NULL) {
            $alias = $methodname;
        }
        if (is_string($belongto)) {
            $this->addFunction(array($belongto, $methodname), $alias, $resultMode, $simple);
        }
        else {
            $this->addFunction(array(&$belongto, $methodname), $alias, $resultMode, $simple);
        }
    }
    public function addMethods($methods, $belongto, $aliases = NULL, $resultMode = HproseResultMode::Normal, $simple = NULL) {
        $aliases_is_null = ($aliases === NULL);
        $count = count($methods);
        if (is_string($aliases)) {
            $aliasPrefix = $aliases;
            $aliases = array();
            foreach ($methods as $k => $method) {
                $aliases[$k] = $aliasPrefix . '_' . $method;
            }
        }
        if (!$aliases_is_null && $count != count($aliases)) {
            throw new Exception('The count of methods is not matched with aliases');
        }
        if($count){
            foreach($methods as $k => $method){
                if (is_string($belongto)) {
                    $function = array($belongto, $method);
                }
                else {
                    $function = array(&$belongto, $method);
                }
                if ($aliases_is_null) {
                    $this->addFunction($function, $method, $resultMode, $simple);
                }
                else {
                    $this->addFunction($function, $aliases[$k], $resultMode, $simple);
                }
            }
        }
    }
    public function addInstanceMethods($object, $class = NULL, $aliasPrefix = NULL, $resultMode = HproseResultMode::Normal, $simple = NULL) {
        if ($class === NULL) $class = get_class($object);
        $this->addMethods($this->getDeclaredOnlyMethods($class), $object, $aliasPrefix, $resultMode, $simple);
    }
    public function addClassMethods($class, $execclass = NULL, $aliasPrefix = NULL, $resultMode = HproseResultMode::Normal, $simple = NULL) {
        if ($execclass === NULL) $execclass = $class;
        $this->addMethods($this->getDeclaredOnlyMethods($class), $execclass, $aliasPrefix, $resultMode, $simple);
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
                        else {
                            return $this->addClassMethods($args[0], NULL, $args[1]);
                        }
                    }
                    return $this->addMethod($args[0], $args[1]);
                }
                elseif (is_array($args[0])) {
                    if (is_array($args[1])) {
                        return $this->addFunctions($args[0], $args[1]);
                    }
                    else {
                        return $this->addMethods($args[0], $args[1]);
                    }
                }
                elseif (is_object($args[0])) {
                    return $this->addInstanceMethods($args[0], $args[1]);
                }
                break;
            }
            case 3: {
                if (is_callable($args[0]) && is_null($args[1]) && is_string($args[2])) {
                    return $this->addFunction($args[0], $args[2]);
                }
                elseif (is_string($args[0]) && is_string($args[2])) {
                    if (is_string($args[1]) && !is_callable(array($args[0], $args[1]))) {
                        return $this->addClassMethods($args[0], $args[1], $args[2]);
                    }
                    else {
                        return $this->addMethod($args[0], $args[1], $args[2]);
                    }
                }
                elseif (is_array($args[0])) {
                    if (is_null($args[1]) && is_array($args[2])) {
                        return $this->addFunctions($args[0], $args[2]);
                    }
                    else {
                        return $this->addMethods($args[0], $args[1], $args[2]);
                    }
                }
                elseif (is_object($args[0])) {
                    return $this->addInstanceMethods($args[0], $args[1], $args[2]);
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
    public function isCrossDomainEnabled() {
        return $this->crossDomain;
    }
    public function setCrossDomainEnabled($enable = true) {
        $this->crossDomain = $enable;
    }
    public function isP3PEnabled() {
        return $this->P3P;
    }
    public function setP3PEnabled($enable = true) {
        $this->P3P = $enable;
    }
    public function isGetEnabled() {
        return $this->get;
    }
    public function setGetEnabled($enable = true) {
        $this->get = $enable;
    }
    public function getFilter() {
        if (count($this->filters) === 0) {
            return NULL;
        }
        return $this->filters[0];
    }
    public function setFilter($filter) {
        $this->filters = array();
        if ($filter !== NULL) {
            $this->filters[] = $filter;
        }
    }
    public function addFilter($filter) {
        $this->filters[] = $filter;
    }
    public function removeFilter($filter) {
        $i = array_search($filter, $this->filters);
        if ($i === false || $i === NULL) {
            return false;
        }
        $this->filters = array_splice($this->filters, $i, 1);
        return true;
    }
    public function addAccessControlAllowOrigin($origin) {
        $this->origins[$origin] = true;
    }
    public function removeAccessControlAllowOrigin($origin) {
        unset($this->origins[$origin]);
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
    public function handle() {
        if (!isset($GLOBALS['HTTP_RAW_POST_DATA'])) $GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents("php://input");
        $this->input = new HproseStringStream($this->inputFilter($GLOBALS['HTTP_RAW_POST_DATA']));
        $this->output = new HproseStringStream();
        set_error_handler(array(&$this, '__errorHandler'), $this->error_types);
        ob_start(array(&$this, "__filterHandler"));
        ob_implicit_flush(0);
        @ob_clean();
        $this->sendHeader();
        if (($_SERVER['REQUEST_METHOD'] == 'GET') and $this->get) {
            return $this->doFunctionList();
        }
        elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                switch ($this->input->getc()) {
                    case HproseTags::TagCall: return $this->doInvoke();
                    case HproseTags::TagEnd: return $this->doFunctionList();
                    default: throw new Exception("Wrong Request: \r\n" . $GLOBALS['HTTP_RAW_POST_DATA']);
                }
            }
            catch (Exception $e) {
                $this->error = $e->getMessage();
                if ($this->debug) {
                    $this->error .= "\nfile: " . $e->getFile() .
                                    "\nline: " . $e->getLine() .
                                    "\ntrace: " . $e->getTraceAsString();
                }
                $this->sendError();
            }
        }
        $this->input->close();
        $this->output->close();
    }
    public function start() {
        $this->handle();
    }
}
?>
