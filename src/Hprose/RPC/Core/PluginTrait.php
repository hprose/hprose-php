<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| PluginTrait.php                                          |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

trait PluginTrait {
    private function sortHandlers(array $handlers): array{
        $invokeHandlers = [];
        $ioHandlers = [];
        foreach ($handlers as $handler) {
            switch (Utils::getNumberOfParameters($handler)) {
            case 4:
                $invokeHandlers[] = $handler;
                break;
            case 3:
                $ioHandlers[] = $handler;
                break;
            default:
                throw new InvalidArgumentException('Invalid parameter type');
            }
        }
        return [$invokeHandlers, $ioHandlers];
    }
    public function use (callable ...$handlers): self {
        [$invokeHandlers, $ioHandlers] = $this->sortHandlers($handlers);
        if (count($invokeHandlers) > 0) {
            $this->invokeManager->use(...$invokeHandlers);
        }
        if (count($ioHandlers) > 0) {
            $this->ioManager->use(...$ioHandlers);
        }
        return $this;
    }
    public function unuse(callable ...$handlers): self {
        [$invokeHandlers, $ioHandlers] = $this->sortHandlers($handlers);
        if (count($invokeHandlers) > 0) {
            $this->invokeManager->unuse(...$invokeHandlers);
        }
        if (count($ioHandlers) > 0) {
            $this->ioManager->unuse(...$ioHandlers);
        }
        return $this;
    }
}