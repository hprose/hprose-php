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
 * Hprose/Base/Service.php                                *
 *                                                        *
 * hprose base service class for php 5.3+                 *
 *                                                        *
 * LastModified: Jun 28, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Base {

    class Service extends \Hprose\Service {
        public $user_fatal_error_handler = null;
        public function __construct() {
            $self = $this;
            register_shutdown_function(function() use ($self) {
                if (!is_callable($self->user_fatal_error_handler)) {
                    return;
                }
                $error = error_get_last();
                if ($error) {
                    switch ($error['type']) {
                        case E_ERROR:
                        case E_PARSE:
                        case E_USER_ERROR:
                        case E_CORE_ERROR:
                        case E_COMPILE_ERROR: {
                            $message = $error['message'];
                            $file = $error['file'];
                            $line = $error['line'];
                            $log = "$message ($file:$line)\nStack trace:\n";
                            $trace = debug_backtrace();
                            foreach ($trace as $i => $t) {
                                if (!isset($t['file'])) {
                                    $t['file'] = 'unknown';
                                }
                                if (!isset($t['line'])) {
                                    $t['line'] = 0;
                                }
                                if (!isset($t['function'])) {
                                    $t['function'] = 'unknown';
                                }
                                $log .= "#$i {$t['file']}({$t['line']}): ";
                                if (isset($t['object']) && is_object($t['object'])) {
                                    $log .= get_class($t['object']) . '->';
                                }
                                $log .= "{$t['function']}()\n";
                            }
                            @ob_end_clean();
                            $user_fatal_error_handler = $self->user_fatal_error_handler;
                            call_user_func($user_fatal_error_handler, $log);
                        }
                    }
                }
            });
        }

        public function defaultHandle($request, $context) {
            $self = $this;
            $error = null;
            set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$error, $self, $context) {
                if ($self->isDebugEnabled()) {
                    $errstr .= " in $errfile on line $errline";
                }
                $err = $self->getErrorTypeString($errno) . ": " . $errstr;
                if ($errno == E_USER_ERROR ||
                    $errno == E_COMPILE_ERROR ||
                    $errno == E_CORE_ERROR ||
                    $errno == E_ERROR ||
                    $errno == E_PARSE) {
                    $err .= ob_get_clean();
                }
                $error = $self->sendError($err, $context);
            }, $this->error_types);

            ob_start();
            ob_implicit_flush(0);

            $result = parent::defaultHandle($request, $context);

            ob_end_clean();
            restore_error_handler();
            return ($error === null) ? $result : $error;
        }
    }
}
