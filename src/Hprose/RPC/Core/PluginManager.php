<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| PluginManager.php                                        |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

abstract class PluginManager {
    private $handlers = [];
    private $defaultHandler;
    public $handler;
    public function __construct($defaultHandler) {
        $this->defaultHandler = $defaultHandler;
        $this->handler = $defaultHandler;
    }

    abstract protected function getNextHandler(callable $handler, callable $next): callable ;

    private function rebuildHandler(): void {
        $handlers = $this->handlers;
        $next = $this->defaultHandler;
        $n = count($handlers);
        foreach ($handlers as $handler) {
            $next = $this->getNextHandler($handler, $next);
        }
        $this->handler = $next;
    }

    public function use (callable ...$handlers): void {
        array_push($this->handlers, ...$handlers);
        $this->rebuildHandler();
    }

    public function unuse(callable ...$handlers): void {
        $rebuild = false;
        foreach ($handlers as $handler) {
            $index = array_search($handler, $this->handlers);
            if ($index !== false) {
                array_splice($this->handlers, $index, 1);
                $rebuild = true;
            }
        }
        if ($rebuild) {
            $this->rebuildHandler();
        }
    }
}