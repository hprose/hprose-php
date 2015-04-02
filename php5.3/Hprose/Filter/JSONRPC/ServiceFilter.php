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
 * Hprose/Filter/JSONRPC/ServiceFilter.php                *
 *                                                        *
 * json rpc service filter class for php 5.3+             *
 *                                                        *
 * LastModified: Apr 1, 2015                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Filter\JSONRPC {
    class ServiceFilter implements \Hprose\Filter {
        function inputFilter($data, $context) {
            if ($data !== "" && $data{0} === '{') {
                $context->userdata->format = "jsonrpc";
                $request = json_decode($data);
                if (isset($request->id)) {
                    $context->userdata->id = $request->id;
                }
                else {
                    $context->userdata->id = null;
                }
                if (isset($request->version)) {
                    $context->userdata->version = $request->version;
                }
                else if (isset($request->jsonrpc)) {
                    $context->userdata->version = $request->jsonrpc;
                }
                else {
                    $context->userdata->version = "1.0";
                }
                $stream = new \Hprose\BytesIO();
                $writer = new \Hprose\Writer($stream, true);
                if (isset($request->method)) {
                    $stream->write(\Hprose\Tags::TagCall);
                    $writer->writeString($request->method);
                    if (isset($request->params)) {
                        $writer->writeArray($request->params);
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
            if (isset($context->userdata->format) && $context->userdata->format === "jsonrpc") {
                $response = new \stdClass();
                $response->id = $context->userdata->id;
                if ($context->userdata->version != "2.0") {
                    if ($context->userdata->version == "1.1") {
                        $response->version = "1.1";
                    }
                    $response->result = null;
                    $response->error = null;
                }
                else {
                    $response->jsonrpc = "2.0";
                }
                if ($data !== "") {
                    $stream = new \Hprose\BytesIO($data);
                    $reader = new \Hprose\Reader($stream);
                    while (($tag = $stream->getc()) !== \Hprose\Tags::TagEnd) {
                        $reader->reset();
                        switch ($tag) {
                            case \Hprose\Tags::TagResult:
                                $response->result = $reader->unserialize();
                                break;
                            case \Hprose\Tags::TagError:
                                $lasterror = error_get_last();
                                $response->error = array("code" => $lasterror["type"], "message" => $reader->unserialize());
                                break;
                            case \Hprose\Tags::TagFunctions:
                                $response->result = $reader->unserialize();
                                break;
                            default:
                                return json_encode($response);
                                break;
                        }
                    }
                }
                $data = json_encode($response);
            }
            return $data;
        }
    }
}
