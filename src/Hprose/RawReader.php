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
 * Hprose/RawReader.php                                   *
 *                                                        *
 * hprose raw reader class for php 5.3+                   *
 *                                                        *
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

use Exception;

class RawReader {
    public $stream;
    public function __construct(BytesIO $stream) {
        $this->stream = $stream;
    }
    public function unexpectedTag($tag, $expectTags = '') {
        if ($tag && $expectTags) {
            return new Exception("Tag '" . $expectTags . "' expected, but '" . $tag . "' found in stream");
        }
        else if ($tag) {
            return new Exception("Unexpected serialize tag '" . $tag . "' in stream");
        }
        else {
            return new Exception('No byte found in stream');
        }
    }
    public function readRaw() {
        $ostream = new BytesIO();
        $this->privateReadRaw($ostream);
        return $ostream;
    }

    private function privateReadRaw(BytesIO $ostream, $tag = '') {
        if ($tag == '') {
            $tag = $this->stream->getc();
        }
        $ostream->write($tag);
        switch ($tag) {
            case '0':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
            case '8':
            case '9':
            case Tags::TagNull:
            case Tags::TagEmpty:
            case Tags::TagTrue:
            case Tags::TagFalse:
            case Tags::TagNaN:
                break;
            case Tags::TagInfinity:
                $ostream->write($this->stream->getc());
                break;
            case Tags::TagInteger:
            case Tags::TagLong:
            case Tags::TagDouble:
            case Tags::TagRef:
                $this->readNumberRaw($ostream);
                break;
            case Tags::TagDate:
            case Tags::TagTime:
                $this->readDateTimeRaw($ostream);
                break;
            case Tags::TagUTF8Char:
                $this->readUTF8CharRaw($ostream);
                break;
            case Tags::TagBytes:
                $this->readBytesRaw($ostream);
                break;
            case Tags::TagString:
                $this->readStringRaw($ostream);
                break;
            case Tags::TagGuid:
                $this->readGuidRaw($ostream);
                break;
            case Tags::TagList:
            case Tags::TagMap:
            case Tags::TagObject:
                $this->readComplexRaw($ostream);
                break;
            case Tags::TagClass:
                $this->readComplexRaw($ostream);
                $this->privateReadRaw($ostream);
                break;
            case Tags::TagError:
                $this->privateReadRaw($ostream);
                break;
            default: throw $this->unexpectedTag($tag);
        }
    }

    private function readNumberRaw(BytesIO $ostream) {
        $s = $this->stream->readuntil(Tags::TagSemicolon) .
             Tags::TagSemicolon;
        $ostream->write($s);
    }

    private function readDateTimeRaw(BytesIO $ostream) {
        $s = '';
        do {
            $tag = $this->stream->getc();
            $s .= $tag;
        } while ($tag != Tags::TagSemicolon &&
                 $tag != Tags::TagUTC);
        $ostream->write($s);
    }

    private function readUTF8CharRaw(BytesIO $ostream) {
        $ostream->write($this->stream->readString(1));
    }

    private function readBytesRaw(BytesIO $ostream) {
        $len = $this->stream->readuntil(Tags::TagQuote);
        $s = $len . Tags::TagQuote . $this->stream->read((int)$len) . Tags::TagQuote;
        $this->stream->skip(1);
        $ostream->write($s);
    }

    private function readStringRaw(BytesIO $ostream) {
        $len = $this->stream->readuntil(Tags::TagQuote);
        $s = $len . Tags::TagQuote . $this->stream->readString((int)$len) . Tags::TagQuote;
        $this->stream->skip(1);
        $ostream->write($s);
    }

    private function readGuidRaw(BytesIO $ostream) {
        $ostream->write($this->stream->read(38));
    }

    private function readComplexRaw(BytesIO $ostream) {
        $s = $this->stream->readuntil(Tags::TagOpenbrace) .
             Tags::TagOpenbrace;
        $ostream->write($s);
        while (($tag = $this->stream->getc()) != Tags::TagClosebrace) {
            $this->privateReadRaw($ostream, $tag);
        }
        $ostream->write($tag);
    }
}
