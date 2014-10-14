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
 * XMLRPCClientFilter.php                                 *
 *                                                        *
 * xml-rpc client filter class for php5.                  *
 *                                                        *
 * LastModified: Oct 14, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

class XMLRPCClientFilter implements HproseFilter {
    function inputFilter($data, $context) {
        $result = xmlrpc_decode($data, "UTF-8");
        if (isset($result->faultString)) {
            $data = HproseTags::TagError . hprose_serialize_string($result->faultString);
        }
        else {
            $data = HproseTags::TagResult . hprose_serialize($result, true);
        }
        $data .= HproseTags::TagEnd;
        return $data;
    }

    function outputFilter($data, $context) {
        $method = NULL;
        $params = array();
        $stream = new HproseStringStream($data);
        $tag = $stream->getc();
        if ($tag === HproseTags::TagCall) {
            $method = hprose_unserialize_with_stream($stream);
            $tag = $stream->getc();
            if ($tag == HproseTags::TagList) {
                $params = &hprose_unserialize_list_with_stream($stream);
            }
        }
        else {
            throw new Exception("Error Processing Request", 1);
        }
        return xmlrpc_encode_request($method, $params);
    }
}

?>