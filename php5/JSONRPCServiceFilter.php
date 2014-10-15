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
 * LastModified: Oct 15, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

class JSONRPCServiceFilter implements HproseFilter {
    function inputFilter($data, $context) {
        if ($data !== "" && $data{0} === '{') {
            $context->userdata->format = "jsonrpc";
            $request = json_decode($data);
            if (isset($request->id)) {
                $context->userdata->id = $request->id;
            }
            else {
                $context->userdata->id = NULL;
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
            $data = "";
            if (isset($request->method)) {
                $data = HproseTags::TagCall . hprose_serialize_string($request->method);
                if (isset($request->params)) {
                    $data .= hprose_serialize_list($request->params, true);
                }
            }
            $data .= HproseTags::TagEnd;
        }
        return $data;
    }

    function outputFilter($data, $context) {
        if (isset($context->userdata->format) && $context->userdata->format === "jsonrpc") {        
            $response = new stdClass();
            $response->id = $context->userdata->id;
            if ($context->userdata->version != "2.0") {
                if ($context->userdata->version == "1.1") {
                    $response->version = "1.1";
                }
                $response->result = NULL;
                $response->error = NULL;
            }
            else {
                $response->jsonrpc = "2.0";
            }
            if ($data !== "") {
                $stream = new HproseStringStream($data);
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
            $data = json_encode($response);
        }
        return $data;
    }
}

?>