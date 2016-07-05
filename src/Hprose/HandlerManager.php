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
 * Hprose/HandlerManager.php                              *
 *                                                        *
 * hprose HandlerManager class for php 5.3+               *
 *                                                        *
 * LastModified: Jul 5, 2015                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose {
    abstract class HandlerManager {
        private $invokeHandlers = array();
        private $beforeFilterHandlers = array();
        private $afterFilterHandlers = array();
        private $defaultInvokeHandler;
        private $defaultBeforeFilterHandler;
        private $defaultAfterFilterHandler;
        protected $invokeHandler;
        protected $beforeFilterHandler;
        protected $afterFilterHandler;
        public function __construct() {
            $self = $this;
            $this->defaultInvokeHandler = function($name, array $args, \stdClass $context) use ($self) {
                $self->invokeHandler($name, $args, $context);
            };
            $this->defaultBeforeFilterHandler = function($request, \stdClass $context) use ($self) {
                $self->beforeFilterHandler($name, $args, $context);
            };
            $this->defaultAfterFilterHandler = function($request, \stdClass $context) use ($self) {
                $self->afterFilterHandler($name, $args, $context);
            };
            $this->$invokeHandler = $this->defaultInvokeHandler;
            $this->$beforeFilterHandler = $this->defaultBeforeFilterHandler;
            $this->$afterFilterHandler = $this->defaultAfterFilterHandler;
        }
        protected abstract function invokeHandler($name, array $args, \stdClass $context);
        protected abstract function beforeFilterHandler($request, \stdClass $context);
        protected abstract function afterFilterHandler($request, \stdClass $context);
        private function getNextInvokeHandler($next, $handler) {
            return function($name, array $args, \stdClass $context) use ($next, $handler) {
                    try {
                        return Future::toFuture(call_user_func($handler, $name, $args, $context, $next));
                    }
                    catch (\Exception $e) {
                        return Future::error($e);
                    }
                    catch (\Throwable $e) {
                        return Future::error($e);
                    }
            };
        }
        private function getNextFilterHandler($next, $handler) {
            return function($request, \stdClass $context) use ($next, $handler) {
                try {
                    return Future::toFuture(call_user_func($handler, $request, $context, $next));
                }
                catch (\Exception $e) {
                    return Future::error($e);
                }
                catch (\Throwable $e) {
                    return Future::error($e);
                }
            };
        }
        public function addInvokeHandler($handler) {
            if ($handler == null) return;
            $this->invokeHandlers[] = $handler;
            $next = $this->defaultInvokeHandler;
            for ($i = count($this->invokeHandlers) - 1; $i >= 0; --$i) {
                $next = $this->getNextInvokeHandler($next, $this->invokeHandlers[$i]);
            }
            $this->invokeHandler = $next;
            return $this;
        }
        public function addBeforeFilterHandler($handler) {
            if ($handler == null) return;
            $this->beforeFilterHandlers[] = $handler;
            $next = $this->defaultBeforeFilterHandler;
            for ($i = count($this->beforeFilterHandlers) - 1; $i >= 0; --$i) {
                $next = $this->getNextFilterHandler($next, $this->beforeFilterHandlers[$i]);
            }
            $this->beforeFilterHandler = $next;
            return $this;
        }
        public function addAfterFilterHandler($handler) {
            if ($handler == null) return;
            $this->afterFilterHandlers[] = $handler;
            $next = $this->defaultAfterFilterHandler;
            for ($i = count($this->afterFilterHandlers) - 1; $i >= 0; --$i) {
                $next = $this->getNextFilterHandler($next, $this->afterFilterHandlers[$i]);
            }
            $this->afterFilterHandler = $next;
            return $this;
        }
    }
}