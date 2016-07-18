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
 * Hprose/Yii/Server.php                                  *
 *                                                        *
 * hprose yii http server class for php 5.3+              *
 *                                                        *
 * LastModified: Jul 18, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Yii;

class Server extends Service {
    public function start() {
        $app = \Yii::$app;
        return $this->handle($app->request, $app->response);
    }
}
