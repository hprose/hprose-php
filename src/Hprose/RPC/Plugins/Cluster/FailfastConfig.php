<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| FailfastConfig.php                                       |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Plugins\Cluster;

class FailfastConfig extends ClusterConfig {
    public $retry = 0;
    public function __construct(callable $onFailure) {
        $this->onFailure = $onFailure;
    }
}