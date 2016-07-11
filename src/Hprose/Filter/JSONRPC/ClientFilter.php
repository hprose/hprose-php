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
 * Hprose/Filter/JSONRPC/ClientFilter.php                 *
 *                                                        *
 * json rpc client filter class for php 5.3+              *
 *                                                        *
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Filter\JSONRPC;

use stdClass;
use Exception;
use Hprose\Filter;
use Hprose\BytesIO;
use Hprose\Writer;
use Hprose\Reader;
use Hprose\Tags;

class ClientFilter implements Filter {
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
    public function inputFilter($data, stdClass $context) {
        $response = json_decode($data);
        if (!isset($response->result)) {
            $response->result = null;
        }
        if (!isset($response->error)) {
            $response->error = null;
        }
        $stream = new BytesIO();
        $writer = new Writer($stream, true);
        if ($response->error) {
            $stream->write(Tags::TagError);
            $writer->writeString($response->error->message);
        }
        else {
            $stream->write(Tags::TagResult);
            $writer->serialize($response->result);
        }
        $stream->write(Tags::TagEnd);
        return $stream->toString();
    }

    public function outputFilter($data, stdClass $context) {
        $request = new stdClass();
        if ($this->version === "1.1") {
            $request->version = "1.1";
        }
        else if ($this->version === "2.0") {
            $request->jsonrpc = "2.0";
        }
        $stream = new BytesIO($data);
        $reader = new Reader($stream);
        $tag = $stream->getc();
        if ($tag === Tags::TagCall) {
            $request->method = $reader->readString();
            $tag = $stream->getc();
            if ($tag == Tags::TagList) {
                $reader->reset();
                $request->params = $reader->readListWithoutTag();
            }
        }
        else {
            throw new Exception("Error Processing Request", 1);
        }
        $request->id = self::$id++;
        return json_encode($request);
    }
}
