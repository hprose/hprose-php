<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Core/PluginManager.php                        |
|                                                          |
| Hprose PluginManager for PHP 7.1+                        |
|                                                          |
| LastModified: Jun 8, 2019                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

abstract class PluginManager {
    private $plugins = [];
    private $defaultPlugin;
    public $plugin;
    public function __construct($defaultPlugin) {
        $this->defaultPlugin = $defaultPlugin;
        $this->plugin = $defaultPlugin;
    }

    abstract protected function getNextPlugin(callable $plugin, callable $next): callable ;

    private function rebuildPlugin(): void {
        $plugins = $this->plugins;
        $next = $this->defaultPlugin;
        $n = count($plugins);
        foreach ($plugins as $plugin) {
            $next = $this->getNextPlugin($plugin, $next);
        }
        $this->plugin = $next;
    }

    public function use (callable ...$plugins): void {
        array_push($this->plugins, ...$plugins);
        $this->rebuildPlugin();
    }

    public function unuse(callable ...$plugins): void {
        $rebuild = false;
        foreach ($plugins as $plugin) {
            $index = array_search($plugin, $this->plugins);
            if ($index !== false) {
                array_splice($this->plugins, $index, 1);
                $rebuild = true;
            }
        }
        if ($rebuild) {
            $this->rebuildPlugin();
        }
    }
}