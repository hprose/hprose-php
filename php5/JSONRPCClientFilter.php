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
 * JSONRPCClientFilter.php                                *
 *                                                        *
 * json rpc client filter class for php5.                 *
 *                                                        *
 * LastModified: Oct 16, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

class JSONRPCClientFilter implements HproseFilter {
    private static $id = 1;
    private $version;
    public function __construct() {
        $this->version = "2.0";
    }
    public function getVersion() {
        return $this->version;
    }
    public function setVersion($version) {
        if ($version === "1.0" || $version === "1.1" || $version === "2.0") {
            $this->version = $version;
        } 
        else {
            throw new Exception("version must be 1.0, 1.1 or 2.0 in string format.");
        }
    }
    function inputFilter($data, $context) {
        $response = json_decode($data);
        if (!isset($response->result)) {
            $response->result = NULL;
        }
        if (!isset($response->error)) {
            $response->error = NULL;
        }
        if ($response->error) {
            $data = HproseTags::TagError . hprose_serialize_string($response->error->message);
        }
        else {
            $data = HproseTags::TagResult . hprose_serialize($response->result, true);
        }
        $data .= HproseTags::TagEnd;
        return $data;
    }

    function outputFilter($data, $context) {
        $request = new stdClass();
        if ($this->version === "1.1") {
            $request->version = "1.1";
        }
        else if ($this->version === "2.0") {
            $request->jsonrpc = "2.0";
        }
        $stream = new HproseStringStream($data);
        $tag = $stream->getc();
        if ($tag === HproseTags::TagCall) {
            $request->method = hprose_unserialize_with_stream($stream);
            $tag = $stream->getc();
            if ($tag == HproseTags::TagList) {
                $request->params = &hprose_unserialize_list_with_stream($stream);
            }
        }
        else {
            throw new Exception("Error Processing Request", 1);
        }
        $request->id = self::$id++;
        return json_encode($request);
    }
}

?>