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
 * HproseRawReader.php                                    *
 *                                                        *
 * hprose raw reader class for php5.                      *
 *                                                        *
 * LastModified: Jul 12, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (!extension_loaded('hprose')) {

require_once('HproseCommon.php');
require_once('HproseTags.php');
require_once('HproseIOStream.php');

class HproseRawReader {
    public $stream;
    function __construct(&$stream) {
        $this->stream = &$stream;
    }
    public function unexpectedTag($tag, $expectTags = NULL) {
        if ($tag && $expectTags) {
            throw new Exception("Tag '" . $expectTags . "' expected, but '" . $tag . "' found in stream");
        }
        else if ($tag) {
            throw new Exception("Unexpected serialize tag '" . $tag . "' in stream");
        }
        else {
            throw new Exception('No byte found in stream');
        }
    }
    public function readRaw($ostream = NULL, $tag = NULL) {
        if (is_null($ostream)) {
            $ostream = new HproseStringStream();
        }
        if (is_null($tag)) {
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
            case HproseTags::TagNull:
            case HproseTags::TagEmpty:
            case HproseTags::TagTrue:
            case HproseTags::TagFalse:
            case HproseTags::TagNaN:
                break;
            case HproseTags::TagInfinity:
                $ostream->write($this->stream->getc());
                break;
            case HproseTags::TagInteger:
            case HproseTags::TagLong:
            case HproseTags::TagDouble:
            case HproseTags::TagRef:
                $this->readNumberRaw($ostream);
                break;
            case HproseTags::TagDate:
            case HproseTags::TagTime:
                $this->readDateTimeRaw($ostream);
                break;
            case HproseTags::TagUTF8Char:
                $this->readUTF8CharRaw($ostream);
                break;
            case HproseTags::TagBytes:
                $this->readBytesRaw($ostream);
                break;
            case HproseTags::TagString:
                $this->readStringRaw($ostream);
                break;
            case HproseTags::TagGuid:
                $this->readGuidRaw($ostream);
                break;
            case HproseTags::TagList:
            case HproseTags::TagMap:
            case HproseTags::TagObject:
                $this->readComplexRaw($ostream);
                break;
            case HproseTags::TagClass:
                $this->readComplexRaw($ostream);
                $this->readRaw($ostream);
                break;
            case HproseTags::TagError:
                $this->readRaw($ostream);
                break;
            default: $this->unexpectedTag($tag);
        }
    	return $ostream;
    }

    private function readNumberRaw($ostream) {
        $s = $this->stream->readuntil(HproseTags::TagSemicolon) .
             HproseTags::TagSemicolon;
        $ostream->write($s);
    }

    private function readDateTimeRaw($ostream) {
        $s = "";
        do {
            $tag = $this->stream->getc();
            $s .= $tag;
        } while ($tag != HproseTags::TagSemicolon &&
                 $tag != HproseTags::TagUTC);
        $ostream->write($s);
    }

    private function readUTF8CharRaw($ostream) {
        $tag = $this->stream->getc();
        $s = $tag;
        $a = ord($tag);
        if (($a & 0xE0) == 0xC0) {
            $s .= $this->stream->getc();
        }
        elseif (($a & 0xF0) == 0xE0) {
            $s .= $this->stream->read(2);
        }
        elseif ($a > 0x7F) {
            throw new Exception("bad utf-8 encoding");
        }
        $ostream->write($s);
    }

    private function readBytesRaw($ostream) {
        $len = $this->stream->readuntil(HproseTags::TagQuote);
        $s = $len . HproseTags::TagQuote . $this->stream->read((int)$len) . HproseTags::TagQuote;
        $this->stream->skip(1);
        $ostream->write($s);
    }

    private function readStringRaw($ostream) {
        $len = $this->stream->readuntil(HproseTags::TagQuote);
        $s = $len . HproseTags::TagQuote;
        $len = (int)$len;
        $this->stream->mark();
        $utf8len = 0;
        for ($i = 0; $i < $len; ++$i) {
            switch (ord($this->stream->getc()) >> 4) {
                case 0:
                case 1:
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                case 7: {
                    // 0xxx xxxx
                    $utf8len++;
                    break;
                }
                case 12:
                case 13: {
                    // 110x xxxx   10xx xxxx
                    $this->stream->skip(1);
                    $utf8len += 2;
                    break;
                }
                case 14: {
                    // 1110 xxxx  10xx xxxx  10xx xxxx
                    $this->stream->skip(2);
                    $utf8len += 3;
                    break;
                }
                case 15: {
                    // 1111 0xxx  10xx xxxx  10xx xxxx  10xx xxxx
                    $this->stream->skip(3);
                    $utf8len += 4;
                    ++$i;
                    break;
                }
                default: {
                    throw new Exception('bad utf-8 encoding');
                }
            }
        }
        $this->stream->reset();
        $this->stream->unmark();
        $s .= $this->stream->read($utf8len) . HproseTags::TagQuote;
        $this->stream->skip(1);
        $ostream->write($s);
    }

    private function readGuidRaw($ostream) {
        $ostream->write($this->stream->read(38));
    }

    private function readComplexRaw($ostream) {
        $s = $this->stream->readuntil(HproseTags::TagOpenbrace) .
             HproseTags::TagOpenbrace;
        $ostream->write($s);
        while (($tag = $this->stream->getc()) != HproseTags::TagClosebrace) {
            $this->readRaw($ostream, $tag);
        }
        $ostream->write($tag);
    }
}

} // endif (!extension_loaded('hprose'))
?>