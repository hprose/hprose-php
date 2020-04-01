<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| WeightedRoundRobinLoadBalance.php                        |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Plugins\LoadBalance;

use Hprose\RPC\Core\Context;

class WeightedRoundRobinLoadBalance extends WeightedLoadBalance {
    private $maxWeight;
    private $gcdWeight;
    private $index = -1;
    private $currentWeight = 0;
    public function __construct(array $uriList) {
        parent::__construct($uriList);
        $this->maxWeight = max($this->weights);
        $this->gcdWeight = array_reduce($this->weights, function ($x, $y) {
            $x = (int) $x;
            $y = (int) $y;
            if ($x < $y) {
                [$x, $y] = [$y, $x];
            }
            while ($y !== 0) {
                [$x, $y] = [$y, $x % $y];
            }
            return $x;
        });
    }
    public function handler(string $request, Context $context, callable $next): string {
        $n = count($this->uris);
        while (true) {
            $this->index = ($this->index + 1) % $n;
            if ($this->index === 0) {
                $this->currentWeight -= $this->gcdWeight;
                if ($this->currentWeight <= 0) {
                    $this->currentWeight = $this->maxWeight;
                }
            }
            if ($this->weights[$this->index] >= $this->currentWeight) {
                $context->uri = $this->uris[$this->index];
                return $next($request, $context);
            }
        }
    }
}