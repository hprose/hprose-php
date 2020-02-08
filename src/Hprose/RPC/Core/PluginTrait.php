<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Core/PluginTrait.php                          |
|                                                          |
| PluginTrait for PHP 7.1+                                 |
|                                                          |
| LastModified: Feb 8, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

trait PluginTrait {
    private function sortPlugins(array $plugins): array{
        $invokePlugins = [];
        $ioPlugins = [];
        foreach ($plugins as $plugin) {
            switch (Utils::getNumberOfParameters($plugin)) {
            case 4:
                $invokePlugins[] = $plugin;
                break;
            case 3:
                $ioPlugins[] = $plugin;
                break;
            default:
                throw new InvalidArgumentException('Invalid parameter type');
            }
        }
        return [$invokePlugins, $ioPlugins];
    }
    public function use (callable ...$plugins): self {
        [$invokePlugins, $ioPlugins] = $this->sortPlugins($plugins);
        if (count($invokePlugins) > 0) {
            $this->invokeManager->use(...$invokePlugins);
        }
        if (count($ioPlugins) > 0) {
            $this->ioManager->use(...$ioPlugins);
        }
        return $this;
    }
    public function unuse(callable ...$plugins): self {
        [$invokePlugins, $ioPlugins] = $this->sortPlugins($plugins);
        if (count($invokePlugins) > 0) {
            $this->invokeManager->unuse(...$invokePlugins);
        }
        if (count($ioPlugins) > 0) {
            $this->ioManager->unuse(...$ioPlugins);
        }
        return $this;
    }
}