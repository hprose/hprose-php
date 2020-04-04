<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| ClusterConfig.php                                        |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Plugins\Cluster;

class ClusterConfig {
    public $retry = 10;
    public $idempotent = false;
    // function onSuccess(Context context): void
    public $onSuccess;
    // function onFailure(Context context): void
    public $onFailure;
    // function onRetry(Context context): int
    public $onRetry;
}