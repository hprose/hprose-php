<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Plugins/WeightedLoadBalance.php               |
|                                                          |
| LastModified: Feb 16, 2020                               |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Plugins;

use Hprose\RPC\Core\Context;
use InvalidArgumentException;

abstract class WeightedLoadBalance {
    protected $uris = [];
    protected $weights = [];
    public function __construct(array $uriList) {
        if (empty($uriList)) {
            throw new InvalidArgumentException('uriList cannot be empty');
        }
        foreach ($uriList as $uri => $weight) {
            $this->uris[] = $uri;
            if ($weight <= 0) {
                throw new InvalidArgumentException('weight must be great than 0');
            }
            $this->weights[] = $weight;
        }
    }
    public abstract function handler(string $request, Context $context, callable $next): string;
}