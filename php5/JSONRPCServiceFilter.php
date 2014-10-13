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
 * JSONRPCServiceFilter.php                               *
 *                                                        *
 * json rpc service filter class for php5.                *
 *                                                        *
 * LastModified: Oct 13, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

class JSONRPCServiceFilter implements HproseFilter {
    function inputFilter($data, $context) {
        $request = json_decode($data);
        if (isset($request->id)) {
            $context->id = $request->id;
        }
        else {
            $context->id = NULL;
        }
        if (isset($request->version)) {
            $context->version = $request->version;            
        }
        else if (isset($request->jsonrpc)) {
            $context->version = $request->jsonrpc;            
        }
        else {
            $context->version = "1.0";
        }
        $data = "";
        if (isset($request->method)) {
            $data = HproseTags::TagCall . hprose_serialize_string($request->method);
            if (isset($request->params)) {
                $data .= hprose_serialize_list($request->params, true);
            }
        }
        $data .= HproseTags::TagEnd;
        return $data;
    }

    function outputFilter($data, $context) {
        $response = new stdClass();
        $response->id = $context->id;
        if ($context->version != "2.0") {
            if ($context->version == "1.1") {
                $response->version = "1.1";
            }
            $response->result = NULL;
            $response->error = NULL;
        }
        else {
            $response->jsonrpc = "2.0";
        }
        $stream = new HproseStringStream($data);
        if ($data !== "") {
            while (($tag = $stream->getc()) !== HproseTags::TagEnd) {
                switch ($tag) {
                    case HproseTags::TagResult:
                        $response->result = &hprose_unserialize_with_stream($stream);
                        break;
                    case HproseTags::TagError:
                        $lasterror = error_get_last();
                        $response->error = array("code" => $lasterror["type"], "message" => hprose_unserialize_with_stream($stream));
                        break;
                    case HproseTags::TagFunctions:
                        $response->result = &hprose_unserialize_with_stream($stream);
                        break;
                    default:
                        return json_encode($response);
                        break;
                }
            }
        }
        return json_encode($response);
    }
}

?>