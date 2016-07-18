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
 * Hprose/Symfony/Service.php                             *
 *                                                        *
 * hprose symfony http service class for php 5.3+         *
 *                                                        *
 * LastModified: Jul 18, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Symfony;

class Service extends \Hprose\Http\Service {
    protected function header($name, $value, $context) {
        $context->response->headers->set($name, $value);
    }
    protected function getAttribute($name, $context) {
        return $context->request->server->get($name);
    }
    protected function hasAttribute($name, $context) {
        return $context->request->server->has($name);
    }
    protected function readRequest($context) {
        return $context->request->getContent();
    }
    protected function createContext($request, $response) {
        $context = parent::createContext();
        $context->request = $request;
        $context->response = $response;
        $context->session = $request->getSession();
        return $context;
    }
    protected function writeResponse($data, $context) {
        echo $context->response->setContent($data);
    }
    protected function isGet($context) {
        return $context->request->isMethod('GET');
    }
    protected function isPost($context) {
        return $context->request->isMethod('POST');
    }
}
