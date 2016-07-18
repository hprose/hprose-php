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
 * Hprose/Symfony/Server.php                              *
 *                                                        *
 * hprose symfony http server class for php 5.3+          *
 *                                                        *
 * LastModified: Jul 18, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Symfony;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Server extends Service {
    public function start() {
        $request = Request::createFromGlobals();
        $response = new Response();
        return $this->handle($request, $response);
    }
}
