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
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Filter\XMLRPC;

use stdClass;
use Hprose\Filter;
use Hprose\BytesIO;
use Hprose\Writer;
use Hprose\Reader;
use Hprose\Tags;

class ServiceFilter implements Filter {
    public function inputFilter($data, stdClass $context) {
        if ($data !== "" && $data{0} === '<') {
            $context->userdata->format = "xmlrpc";
            $method = null;
            $params = xmlrpc_decode_request($data, $method, "UTF-8");
            $stream = new BytesIO();
            $writer = new Writer($stream, true);
            if (isset($method)) {
                $stream->write(Tags::TagCall);
                $writer->writeString($method);
                if (isset($params)) {
                    $writer->writeArray($params);
                }
            }
            $stream->write(Tags::TagEnd);
            $data = $stream->toString();
        }
        return $data;
    }

    public function outputFilter($data, stdClass $context) {
        if (isset($context->userdata->format) && $context->userdata->format === "xmlrpc") {
            $result = null;
            if ($data !== "") {
                $stream = new BytesIO($data);
                $reader = new Reader($stream);
                while (($tag = $stream->getc()) !== Tags::TagEnd) {
                    $reader->reset();
                    switch ($tag) {
                        case Tags::TagResult:
                            $result = $reader->unserialize();
                            break;
                        case Tags::TagError:
                            $lasterror = error_get_last();
                            $result = array(
                                "faultCode" => $lasterror["type"],
                                "faultString" => $reader->unserialize()
                            );
                            break;
                        case Tags::TagFunctions:
                            $result = $reader->unserialize();
                            break;
                        default:
                            return xmlrpc_encode($result);
                    }
                }
            }
            $data = xmlrpc_encode($result);
        }
        return $data;
    }
}
