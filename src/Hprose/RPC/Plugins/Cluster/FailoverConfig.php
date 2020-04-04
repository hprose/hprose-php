<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| FailoverConfig.php                                       |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Plugins\Cluster;

class FailoverConfig extends ClusterConfig {
    use Singleton;
    public function __construct(int $retry = 10, float $minInterval = 0.5, float $maxInterval = 5.0) {
        $this->retry = $retry;
        $index = 0;
        $this->onFailure = function (Context $context): void {
            $uris = $context->client->getUris();
            $n = count($uris);
            if ($n > 1) {
                $index = ($index + 1) % $n;
                $context->uri = $uris[$index];
            }
        };
        $this->onRetry = function (Context $context): float {
            $context->retried++;
            $interval = ($context->retried - count($context->client->getUris())) * $minInterval;
            if ($interval > $maxInterval) {
                $interval = $maxInterval;
            }
            return $interval;
        };
    }
}