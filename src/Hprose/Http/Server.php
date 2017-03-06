<?php
/**********************************************************\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: http://www.hprose.com/                 |
|                   http://www.hprose.org/                 |
|                                                          |
\**********************************************************/

/**********************************************************\
 *                                                        *
 * Hprose/Http/Server.php                                 *
 *                                                        *
 * hprose http server class for php 5.3+                  *
 *                                                        *
 * LastModified: Jul 17, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Http;

class Server extends Service {
    public function start() {
        $this->handle();
    }
}
