<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| WeightedRandomLoadBalance.php                            |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Plugins\LoadBalance;

use Hprose\RPC\Core\Context;
use Throwable;

class WeightedRandomLoadBalance extends WeightedLoadBalance {
    private $effectiveWeights;
    public function __construct(array $uriList) {
        parent::__construct($uriList);
        $this->effectiveWeights = $this->weights;
    }
    public function handler(string $request, Context $context, callable $next): string {
        $n = count($this->uris);
        $index = $n - 1;
        $totalWeight = array_sum($this->effectiveWeights);
        if ($totalWeight > 0) {
            $currentWeight = random_int(0, $totalWeight - 1);
            for ($i = 0; $i < $n; ++$i) {
                $currentWeight -= $this->effectiveWeights[$i];
                if ($currentWeight < 0) {
                    $index = $i;
                    break;
                }
            }
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