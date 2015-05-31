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
 * LastModified: May 31, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Filter\JSONRPC {
    class ServiceFilter implements \Hprose\Filter {
        function inputFilter($data, $context) {
            if ($data !== "" && ($data{0} === '[' || $data{0} === '{')) {
                try {
                    $requests = json_decode($data);
                }
                catch (\Exception $e) {
                    return $data;
                }
                if ($data{0} === '{') {
                    $requests = array($requests);
                }
                else if (count($requests) === 0) {
                    return $data;
                }
                $stream = new \Hprose\BytesIO();
                $writer = new \Hprose\Writer($stream, true);
                $context->userdata->jsonrpc = array();
                foreach ($requests as $request) {
                    $jsonrpc = new \stdClass();
                    if (isset($request->id)) {
                        $jsonrpc->id = $request->id;
                    }
                    else {
                        $jsonrpc->id = null;
                    }
                    if (isset($request->version)) {
                        $jsonrpc->version = $request->version;
                    }
                    else if (isset($request->jsonrpc)) {
                        $jsonrpc->version = $request->jsonrpc;
                    }
                    else {
                        $jsonrpc->version = '1.0';
                    }
                    $context->userdata->jsonrpc[] = $jsonrpc;
                    if (isset($request->method)) {
                        $stream->write(\Hprose\Tags::TagCall);
                        $writer->writeString($request->method);
                        if (isset($request->params) &&
                            count($request->params) > 0) {
                            $writer->writeArray($request->params);
                        }
                    }
                    else {
                        unset($context->userdata->jsonrpc);
                        return $data;
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
            if (isset($context->userdata->jsonrpc)) {
                $responses = array();
                $stream = new \Hprose\BytesIO($data);
                $reader = new \Hprose\Reader($stream);
                $tag = $stream->getc();
                foreach ($context->userdata->jsonrpc as $jsonrpc) {
                    $response = new \stdClass();
                    $response->id = $jsonrpc->id;
                    $version = $jsonrpc->version;
                    if ($version !== '2.0') {
                        if ($version === '1.1') {
                            $response->version = '1.1';
                        }
                        $response->result = null;
                        $response->error = null;
                    }
                    else {
                        $response->jsonrpc = '2.0';
                    }
                    if ($tag !== \Hprose\Tags::TagEnd) {
                        $reader->reset();
                        if ($tag === \Hprose\Tags::TagResult) {
                            $response->result = $reader->unserialize();
                        }
                        else if ($tag === \Hprose\Tags::TagError) {
                            $lasterror = error_get_last();
                            $response->error = new \stdClass();
                            $response->error->code = $lasterror['type'];
                            $response->error->message = $reader->unserialize();
                        }
                        $tag = $stream->getc();
                    }
                    else {
                        $response->result = null;
                    }
                    if ($response->id !== null) {
                        $responses[] = $response;
                    }
                }
                if (count($context->userdata->jsonrpc) === 1) {
                    if (count($responses) === 1) {
                        return json_encode($responses[0]);
                    }
                    return '';
                }
                return json_encode($responses);
            }
            return $data;
        }
    }
}
