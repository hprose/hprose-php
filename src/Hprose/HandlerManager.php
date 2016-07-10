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
 * LastModified: Jul 10, 2015                             *
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
        public function __construct($sync = true) {
            $self = $this;
            $this->defaultInvokeHandler = function($name, array &$args, \stdClass $context) use ($self) {
                return $self->invokeHandler($name, $args, $context);
            };
            $this->defaultBeforeFilterHandler = function($request, \stdClass $context) use ($self) {
                return $self->beforeFilterHandler($request, $context);
            };
            $this->defaultAfterFilterHandler = function($request, \stdClass $context) use ($self) {
                return $self->afterFilterHandler($request, $context);
            };
            $this->invokeHandler = $this->defaultInvokeHandler;
            $this->beforeFilterHandler = $this->defaultBeforeFilterHandler;
            $this->afterFilterHandler = $this->defaultAfterFilterHandler;
        }
        protected abstract function invokeHandler($name, array &$args, \stdClass $context);
        protected abstract function beforeFilterHandler($request, \stdClass $context);
        protected abstract function afterFilterHandler($request, \stdClass $context);
        protected abstract function getNextInvokeHandler($next, $handler);
        protected abstract function getNextFilterHandler($next, $handler);
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