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
 * XMLRPCServiceFilter.php                                *
 *                                                        *
 * xml-rpc service filter class for php5.                 *
 *                                                        *
 * LastModified: Oct 14, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

class XMLRPCServiceFilter implements HproseFilter {
    function inputFilter($data, $context) {
        if ($data !== "" && $data{0} === '<') {
            $context->userdata->format = "xmlrpc";
            $method = NULL;
            $params = xmlrpc_decode_request($data, $method, "UTF-8");
            $data = "";
            if ($method) {
                $data = HproseTags::TagCall . hprose_serialize_string($method);
                if ($params) {
                    $data .= hprose_serialize_list($params, true);
                }
            }
            $data .= HproseTags::TagEnd;
        }
        return $data;
    }

    function outputFilter($data, $context) {
        if (isset($context->userdata->format) && $context->userdata->format === "xmlrpc") {        
            $result = NULL;
            $stream = new HproseStringStream($data);
            if ($data !== "") {
                while (($tag = $stream->getc()) !== HproseTags::TagEnd) {
                    switch ($tag) {
                        case HproseTags::TagResult:
                            $result = &hprose_unserialize_with_stream($stream);
                            break;
                        case HproseTags::TagError:
                            $lasterror = error_get_last();
                            $result = array("faultCode" => $lasterror["type"], "faultString" => hprose_unserialize_with_stream($stream));
                            break;
                        case HproseTags::TagFunctions:
                            $result = &hprose_unserialize_with_stream($stream);
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

?>