<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| NginxRoundRobinLoadBalance.php                           |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Plugins\LoadBalance;

use Hprose\RPC\Core\Context;
use Throwable;

class NginxRoundRobinLoadBalance extends WeightedLoadBalance {
    private $effectiveWeights;
    private $currentWeights;
    public function __construct(array $uriList) {
        parent::__construct($uriList);
        $this->effectiveWeights = $this->weights;
        $this->currentWeights = array_fill(0, count($this->uris), 0);
    }
    public function handler(string $request, Context $context, callable $next): string {
        $n = count($this->uris);
        $index = -1;
        $totalWeight = array_sum($this->effectiveWeights);
        if ($totalWeight > 0) {
            $currentWeight = log(0);
            for ($i = 0; $i < $n; ++$i) {
                $weight = ($this->currentWeights[$i] += $this->effectiveWeights[$i]);
                if ($currentWeight < $weight) {
                    $currentWeight = $weight;
                    $index = $i;
                }
            }
            $this->currentWeights[$index] = $currentWeight - $totalWeight;
        } else {
            $index = random_int(0, $n - 1);
        }
        $context->uri = $this->uris[$index];
        try {
            $response = $next($request, $context);
            if ($this->effectiveWeights[$index] < $this->weights[$index]) {
                ++$this->effectiveWeights[$index];
            }
            return $response;
        } catch (Throwable $e) {
            if ($this->effectiveWeights[$index] > 0) {
                --$this->effectiveWeights[$index];
            }
            throw $e;
        }
    }
}