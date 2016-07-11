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
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Filter\XMLRPC;

use stdClass;
use Exception;
use Hprose\Filter;
use Hprose\BytesIO;
use Hprose\Writer;
use Hprose\Reader;
use Hprose\Tags;

class ClientFilter implements Filter {
    public function inputFilter($data, stdClass $context) {
        $result = xmlrpc_decode($data, "UTF-8");
        $stream = new BytesIO();
        $writer = new Writer($stream, true);
        if (isset($result['faultString'])) {
            $stream->write(Tags::TagError);
            $writer->writeString($result['faultString']);
        }
        else {
            $stream->write(Tags::TagResult);
            $writer->serialize($result);
        }
        $stream->write(Tags::TagEnd);
        return $stream->toString();
    }

    public function outputFilter($data, stdClass $context) {
        $method = null;
        $params = array();
        $stream = new BytesIO($data);
        $reader = new Reader($stream);
        $tag = $stream->getc();
        if ($tag === Tags::TagCall) {
            $method = $reader->readString();
            $tag = $stream->getc();
            if ($tag ==Tags::TagList) {
                $reader->reset();
                $params = $reader->readListWithoutTag();
            }
        }
        else {
            throw new Exception("Error Processing Request", 1);
        }
        return xmlrpc_encode_request($method, $params);
    }
}
