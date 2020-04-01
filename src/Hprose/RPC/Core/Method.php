<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Method.php                                               |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

use InvalidArgumentException;

class Method {
    public $missing = false;
    public $passContext = false;
    public $options = [];
    public $callable;
    public $fullname;
    public $paramTypes = [];
    public function __construct(callable $callable, ?string $fullname = null) {
        $this->callable = $callable;
        $reflection = Utils::getReflectionCallable($callable);
        if (empty($fullname)) {
            $fullname = $reflection->getShortName();
            if (empty($fullname)) {
                throw new InvalidArgumentException('fullname must not be empty');
            }
        }
        $this->fullname = $fullname;
        $params = $reflection->getParameters();
        foreach ($params as $param) {
            $type = $param->getType();
            if ($type === null) {
                $this->paramTypes[] = null;
            } else {
                $this->paramTypes[] = $type->getName();
            }
        }
        $lastParamType = end($this->paramTypes);
        if ($lastParamType === 'Hprose\\RPC\\Core\\Context' || $lastParamType === 'Hprose\\RPC\\Core\\ServiceContext') {
            $this->passContext = true;
            array_pop($this->paramTypes);
        } else {
            reset($this->paramTypes);
        }
    }
}
