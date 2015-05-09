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
 * Hprose/Filter/XMLRPC/ClientFilter.php                  *
 *                                                        *
 * xml-rpc client filter class for php 5.3+               *
 *                                                        *
 * LastModified: Apr 2, 2015                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Filter\XMLRPC {
    class ClientFilter implements \Hprose\Filter {
        function inputFilter($data, $context) {
            $result = xmlrpc_decode($data, "UTF-8");
            $stream = new \Hprose\BytesIO();
            $writer = new \Hprose\Writer($stream, true);
            if (isset($result['faultString'])) {
                $stream->write(\Hprose\Tags::TagError);
                $writer->writeString($result['faultString']);
            }
            else {
                $stream->write(\Hprose\Tags::TagResult);
                $writer->serialize($result);
            }
            $stream->write(\Hprose\Tags::TagEnd);
            $data = $stream->toString();
            unset($result);
            unset($writer);
            unset($stream);
            return $data;
        }

        function outputFilter($data, $context) {
            $method = null;
            $params = array();
            $stream = new \Hprose\BytesIO($data);
            $reader = new \Hprose\Reader($stream);
            $tag = $stream->getc();
            if ($tag === \Hprose\Tags::TagCall) {
                $method = $reader->readString();;
                $tag = $stream->getc();
                if ($tag == \Hprose\Tags::TagList) {
                    $reader->reset();
                    $params = $reader->readListWithoutTag();
                }
            }
            else {
                throw new \Exception("Error Processing Request", 1);
            }
            unset($reader);
            unset($stream);
            return xmlrpc_encode_request($method, $params);
        }
    }
}
