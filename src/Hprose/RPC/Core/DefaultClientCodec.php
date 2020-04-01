<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| DefaultClientCodec.php                                   |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

use Exception;
use Hprose\BytesIO;
use Hprose\Reader;
use Hprose\Tags;
use Hprose\Writer;

class DefaultClientCodec implements ClientCodec {
    use Singleton;
    public $simple = false;
    public function encode(string $name, array &$args, ClientContext $context): string {
        $stream = new BytesIO();
        $writer = new Writer($stream, $this->simple);
        $headers = $context->requestHeaders;
        if ($this->simple) {
            $headers['simple'] = true;
        }
        if (!empty($headers)) {
            $stream->write(Tags::TagHeader);
            $writer->serialize($headers);
            $writer->reset();
        }
        $stream->write(Tags::TagCall);
        $writer->serialize($name);
        if (!empty($args)) {
            $writer->reset();
            $writer->serialize($args);
        }
        $stream->write(Tags::TagEnd);
        return $stream->toString();
    }
    public function decode(string $response, ClientContext $context) {
        $stream = new BytesIO($response);
        $reader = new Reader($stream);
        $tag = $stream->getc();
        if ($tag === Tags::TagHeader) {
            $headers = $reader->unserialize();
            $context->responseHeaders = array_merge($context->responseHeaders, $headers);
            $reader->reset();
            $tag = $stream->getc();
        }
        switch ($tag) {
        case Tags::TagResult:
            if (isset($context->responseHeaders['simple'])) {
                $reader = new Reader($stream, true);
            }
            return $reader->unserialize();
        case Tags::TagError:
            throw new Exception((string) $reader->unserialize());
        case Tags::TagEnd:
            return null;
        default:
            throw new Exception('Invalid response:\r\n'+$response);
        }
    }
}
