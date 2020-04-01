<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| WeightedLoadBalance.php                                  |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Plugins\LoadBalance;

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