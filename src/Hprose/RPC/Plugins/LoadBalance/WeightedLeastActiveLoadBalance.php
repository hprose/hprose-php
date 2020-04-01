<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| WeightedLeastActiveLoadBalance.php                       |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Plugins\LoadBalance;

use Hprose\RPC\Core\Context;
use Throwable;

class WeightedLeastActiveLoadBalance extends WeightedLoadBalance {
    private $effectiveWeights;
    private $actives;
    public function __construct(array $uriList) {
        parent::__construct($uriList);
        $this->effectiveWeights = $this->weights;
        $this->actives = array_fill(0, count($this->uris), 0);
    }
    public function handler(string $request, Context $context, callable $next): string {
        $leastActive = min($this->actives);
        $leastActiveIndexes = [];
        $totalWeight = 0;
        $n = count($this->weights);
        for ($i = 0; $i < $n; ++$i) {
            if ($this->actives[$i] === $leastActive) {
                $leastActiveIndexes[] = $i;
                $totalWeight += $this->effectiveWeights[$i];
            }
        }
        $index = $leastActiveIndexes[0];
        $n = count($leastActiveIndexes);
        if ($n > 1) {
            if ($totalWeight > 0) {
                $currentWeight = random_int(0, $totalWeight - 1);
                for ($i = 0; $i < $n; ++$i) {
                    $currentWeight -= $this->effectiveWeights[$leastActiveIndexes[$i]];
                    if ($currentWeight < 0) {
                        $index = $leastActiveIndexes[$i];
                        break;
                    }
                }
            } else {
                $index = $leastActiveIndexes[random_int(0, $n - 1)];
            }
        }
        $context->uri = $this->uris[$index];
        ++$this->actives[$index];
        try {
            $response = $next($request, $context);
            --$this->actives[$index];
            if ($this->effectiveWeights[$index] < $this->weights[$index]) {
                ++$this->effectiveWeights[$index];
            }
            return $response;
        } catch (Throwable $e) {
            --$this->actives[$index];
            if ($this->effectiveWeights[$index] > 0) {
                --$this->effectiveWeights[$index];
            }
            throw $e;
        }
    }
}