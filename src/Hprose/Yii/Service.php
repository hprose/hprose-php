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
 * Hprose/Yii/Service.php                                 *
 *                                                        *
 * hprose yii http service class for php 5.3+             *
 *                                                        *
 * LastModified: Jul 18, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Yii;

class Service extends \Hprose\Http\Service {
    const ORIGIN = 'Origin';
    protected function header($name, $value, $context) {
        $context->response->headers->set($name, $value);
    }
    protected function getAttribute($name, $context) {
        return $context->request->headers->get($name);
    }
    protected function hasAttribute($name, $context) {
        return $context->request->headers->has($name);
    }
    protected function readRequest($context) {
        return $context->request->rawBody;
    }
    protected function createContext($request, $response) {
        $context = parent::createContext();
        $context->request = $request;
        $context->response = $response;
        $context->session = $request->session;
        return $context;
    }
    protected function writeResponse($data, $context) {
        $context->response->format = \yii\web\Response::FORMAT_RAW;
        $context->response->data = $data;
    }
    protected function isGet($context) {
        return $context->request->isGet;
    }
    protected function isPost($context) {
        return $context->request->isPost;
    }
}
