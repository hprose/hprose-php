<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| DefaultServiceCodec.php                                  |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

use ErrorException;
use Exception;
use Hprose\BytesIO;
use Hprose\Reader;
use Hprose\Tags;
use Hprose\Writer;
use Throwable;

class DefaultServiceCodec implements ServiceCodec {
    use Singleton;
    use ErrorLevel;
    public $debug = false;
    public $simple = false;
    public function encode($result, ServiceContext $context): string {
        $stream = new BytesIO();
        $writer = new Writer($stream, $this->simple);
        $headers = $context->responseHeaders;
        if ($this->simple) {
            $headers['simple'] = true;
        }
        if (!empty($headers)) {
            $stream->write(Tags::TagHeader);
            $writer->serialize($headers);
            $writer->reset();
        }
        if ($result instanceof ErrorException) {
            $stream->write(Tags::TagError);
            $writer->serialize(
                $this->debug ?
                $this->errorLevel[$result->getSeverity()] . ": '" . $result->getMessage() . "'" . " in " . $result->getFile() . ":" . $result->getLine() :
                $result->getMessage()
            );
        } else if ($result instanceof Throwable) {
            $stream->write(Tags::TagError);
            $writer->serialize(
                $this->debug ?
                $result->getMessage() . "\n" . $result->getTraceAsString() :
                $result->getMessage()
            );
        } else {
            $stream->write(Tags::TagResult);
            $writer->serialize($result);
        }
        $stream->write(Tags::TagEnd);
        return $stream->toString();
    }
    private function decodeMethod(string $fullname, ServiceContext $context): Method {
        $service = $context->service;
        $method = $service->get($fullname);
        if (!isset($method)) {
            throw new Exception('Can\'t find this method ' . $fullname . '().');
        }
        $context->method = $method;
        return $method;
    }
    private function decodeArguments(Method $method, BytesIO $stream, Reader $reader): array{
        $tag = $stream->getc();
        if ($method->missing) {
            if ($tag === Tags::TagList) {
                $reader->reset();
                return $reader->readListWithoutTag();
            }
            return [];
        }
        $args = [];
        if ($tag === Tags::TagList) {
            $reader->reset();
            $args = $reader->readListWithoutTag();
            $count = count($args);
            $paramTypes = $method->paramTypes;
            for ($i = 0; $i < $count; ++$i) {
                if (!empty($paramTypes[$i])) {
                    switch ($paramTypes[$i]) {
                    case 'int':
                        $args[$i] = (int) $args[$i];
                        break;
                    case 'bool':
                        $args[$i] = (bool) $args[$i];
                        break;
                    case 'float':
                        $args[$i] = (float) $args[$i];
                        break;
                    case 'string':
                        $args[$i] = (string) $args[$i];
                        break;
                    case 'array':
                    case 'iterable':
                        $args[$i] = (array) $args[$i];
                        break;
                    }
                }
            }
        }
        return $args;
    }

    public function decode(string $request, ServiceContext $context): array{
        if (empty($request)) {
            $this->decodeMethod('~', $context);
            return ['~', []];
        }
        $stream = new BytesIO($request);
        $reader = new Reader($stream);
        $tag = $stream->getc();
        if ($tag === Tags::TagHeader) {
            $headers = $reader->unserialize();
            $context->requestHeaders = array_merge($context->requestHeaders, $headers);
            $reader->reset();
            $tag = $stream->getc();
        }
        switch ($tag) {
        case Tags::TagCall:
            if (isset($context->requestHeaders['simple'])) {
                $reader = new Reader($stream, true);
            }
            $fullname = $reader->readString();
            $args = $this->decodeArguments($this->decodeMethod($fullname, $context), $stream, $reader);
            return [$fullname, $args];
        case Tags::TagEnd:
            $this->decodeMethod('~', $context);
            return ['~', []];
        default:
            throw new Exception('Invalid request:\r\n' . $stream->toString());
        }
    }
}