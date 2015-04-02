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
 * Hprose/Filter/XMLRPC/ServiceFilter.php                 *
 *                                                        *
 * xml-rpc service filter class for php 5.3+              *
 *                                                        *
 * LastModified: Apr 1, 2015                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Filter\XMLRPC {
    class ServiceFilter implements \Hprose\Filter {
        function inputFilter($data, $context) {
            if ($data !== "" && $data{0} === '<') {
                $context->userdata->format = "xmlrpc";
                $method = null;
                $params = xmlrpc_decode_request($data, $method, "UTF-8");
                $stream = new \Hprose\BytesIO();
                $writer = new \Hprose\Writer($stream, true);
                if (isset($method)) {
                    $stream->write(\Hprose\Tags::TagCall);
                    $writer->writeString($method);
                    if (isset($params)) {
                        $writer->writeArray($params);
                    }
                }
                $stream->write(\Hprose\Tags::TagEnd);
                $data = $stream->toString();
                unset($stream);
                unset($writer);
            }
            return $data;
        }

        function outputFilter($data, $context) {
            if (isset($context->userdata->format) && $context->userdata->format === "xmlrpc") {
                $result = null;
                if ($data !== "") {
                    $stream = new \Hprose\BytesIO($data);
                    $reader = new \Hprose\Reader($stream);
                    while (($tag = $stream->getc()) !== \Hprose\Tags::TagEnd) {
                        $reader->reset();
                        switch ($tag) {
                            case \Hprose\Tags::TagResult:
                                $result = $reader->unserialize();
                                break;
                            case \Hprose\Tags::TagError:
                                $lasterror = error_get_last();
                                $result = array("faultCode" => $lasterror["type"], "faultString" => $reader->unserialize());
                                break;
                            case \Hprose\Tags::TagFunctions:
                                $result = $reader->unserialize();
                                break;
                            default:
                                return xmlrpc_encode($result);
                                break;
                        }
                    }
                }
                $data = xmlrpc_encode($result);
            }
            return $data;
        }
    }
}
