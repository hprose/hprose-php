<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Cluster.php                                              |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Plugins\Cluster;
use Throwable;

class Cluster {
    public $config;
    public function __construct(ClusterConfig $config = null) {
        if ($config === null) {
            $this->config = FailoverConfig::getInstance();
        } else {
            $this->config = $config;
        }
    }
    public function handler(string $request, Context $context, callable $next): string {
        $config = $this->config;
        try {
            $response = $next($request, $context);
            if ($config->onSuccess !== null) {
                $config->onSuccess($context);
            }
            return $response;
        } catch (Throwable $e) {
            if ($config->onFailure !== null) {
                $config->onFailure($context);
            }
            if ($config->onRetry !== null) {
                $idempotent = ($context->idempotent === null) ? $config->idempotent : $context->idempotent;
                $retry = ($context->retry === null) ? $config->retry : $context->retry;
                if (!isset($context->retried)) {
                    $context->retried = 0;
                }
                if ($idempotent && $context->retried < $retry) {
                    $interval = $config->onRetry($context);
                    if ($interval > 0) {
                        $this->sleep($interval);
                    }
                    return $this->handler($request, $context, $next);
                }
            }
            throw $e;
        }
    }
    protected function sleep(float $interval): void {
        $seconds = (int) $interval;
        $nanoseconds = (int) (($interval - $seconds) * 1000000000);
        time_nanosleep($seconds, $nanoseconds);
    }
}