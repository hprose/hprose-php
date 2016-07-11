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
 * Hprose/Reader.php                                      *
 *                                                        *
 * hprose reader class for php 5.3+                       *
 *                                                        *
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

use stdClass;
use Exception;
use ReflectionClass;
use SplFixedArray;

class Reader extends RawReader {
    private $classref;
    private $refer;
    public function __construct(BytesIO $stream, $simple = false) {
        parent::__construct($stream);
        $this->classref = array();
        $this->refer = $simple ? new FakeReaderRefer() : new RealReaderRefer();
    }
    public function unserialize() {
        $tag = $this->stream->getc();
        switch ($tag) {
            case '0': return 0;
            case '1': return 1;
            case '2': return 2;
            case '3': return 3;
            case '4': return 4;
            case '5': return 5;
            case '6': return 6;
            case '7': return 7;
            case '8': return 8;
            case '9': return 9;
            case Tags::TagInteger: return $this->readIntegerWithoutTag();
            case Tags::TagLong: return $this->readLongWithoutTag();
            case Tags::TagDouble: return $this->readDoubleWithoutTag();
            case Tags::TagNull: return null;
            case Tags::TagEmpty: return '';
            case Tags::TagTrue: return true;
            case Tags::TagFalse: return false;
            case Tags::TagNaN: return log(-1);
            case Tags::TagInfinity: return $this->readInfinityWithoutTag();
            case Tags::TagDate: return $this->readDateWithoutTag();
            case Tags::TagTime: return $this->readTimeWithoutTag();
            case Tags::TagBytes: return $this->readBytesWithoutTag();
            case Tags::TagUTF8Char: return $this->readUTF8CharWithoutTag();
            case Tags::TagString: return $this->readStringWithoutTag();
            case Tags::TagGuid: return $this->readGuidWithoutTag();
            case Tags::TagList: return $this->readListWithoutTag();
            case Tags::TagMap: return $this->readMapWithoutTag();
            case Tags::TagClass: $this->readClass(); return $this->readObject();
            case Tags::TagObject: return $this->readObjectWithoutTag();
            case Tags::TagRef: return $this->readRef();
            case Tags::TagError: throw new Exception($this->privateReadString());
            default: throw $this->unexpectedTag($tag);
        }
    }
    private function unserializeKey() {
        $tag = $this->stream->getc();
        switch ($tag) {
            case '0': return 0;
            case '1': return 1;
            case '2': return 2;
            case '3': return 3;
            case '4': return 4;
            case '5': return 5;
            case '6': return 6;
            case '7': return 7;
            case '8': return 8;
            case '9': return 9;
            case Tags::TagInteger: return $this->readIntegerWithoutTag();
            case Tags::TagLong:
            case Tags::TagDouble: return $this->readLongWithoutTag();
            case Tags::TagNull: return 'null';
            case Tags::TagEmpty: return '';
            case Tags::TagTrue: return 'true';
            case Tags::TagFalse: return 'false';
            case Tags::TagNaN: return (string)log(-1);
            case Tags::TagInfinity: return (string)$this->readInfinityWithoutTag();
            case Tags::TagBytes: return $this->readBytesWithoutTag();
            case Tags::TagUTF8Char: return $this->readUTF8CharWithoutTag();
            case Tags::TagString: return $this->readStringWithoutTag();
            case Tags::TagGuid: return $this->readGuidWithoutTag();
            case Tags::TagRef: return (string)$this->readRef();
            case Tags::TagError: throw new Exception($this->privateReadString());
            default: throw $this->unexpectedTag($tag);
        }
    }
    public function checkTag($expectTag, $tag = null) {
        if ($tag === null) {
            $tag = $this->stream->getc();
        }
        if ($tag != $expectTag) {
            throw $this->unexpectedTag($tag, $expectTag);
        }
    }
    public function checkTags($expectTags, $tag = null) {
        if ($tag === null) {
            $tag = $this->stream->getc();
        }
        if (!strchr($expectTags, $tag)) {
            throw $this->unexpectedTag($tag, $expectTags);
        }
        return $tag;
    }
    public function readIntegerWithoutTag() {
        return (int)($this->stream->readuntil(Tags::TagSemicolon));
    }
    public function readInteger() {
        $tag = $this->stream->getc();
        switch ($tag) {
            case '0': return 0;
            case '1': return 1;
            case '2': return 2;
            case '3': return 3;
            case '4': return 4;
            case '5': return 5;
            case '6': return 6;
            case '7': return 7;
            case '8': return 8;
            case '9': return 9;
            case Tags::TagInteger: return $this->readIntegerWithoutTag();
            default: throw $this->unexpectedTag($tag);
        }
    }
    public function readLongWithoutTag() {
        return $this->stream->readuntil(Tags::TagSemicolon);
    }
    public function readLong() {
        $tag = $this->stream->getc();
        switch ($tag) {
            case '0': return '0';
            case '1': return '1';
            case '2': return '2';
            case '3': return '3';
            case '4': return '4';
            case '5': return '5';
            case '6': return '6';
            case '7': return '7';
            case '8': return '8';
            case '9': return '9';
            case Tags::TagInteger:
            case Tags::TagLong: return $this->readLongWithoutTag();
            default: throw $this->unexpectedTag($tag);
        }
    }
    public function readDoubleWithoutTag() {
        return (float)($this->stream->readuntil(Tags::TagSemicolon));
    }
    public function readDouble() {
        $tag = $this->stream->getc();
        switch ($tag) {
            case '0': return 0.0;
            case '1': return 1.0;
            case '2': return 2.0;
            case '3': return 3.0;
            case '4': return 4.0;
            case '5': return 5.0;
            case '6': return 6.0;
            case '7': return 7.0;
            case '8': return 8.0;
            case '9': return 9.0;
            case Tags::TagInteger:
            case Tags::TagLong:
            case Tags::TagDouble: return $this->readDoubleWithoutTag();
            case Tags::TagNaN: return log(-1);
            case Tags::TagInfinity: return $this->readInfinityWithoutTag();
            default: throw $this->unexpectedTag($tag);
        }
    }
    public function readNaN() {
        $this->checkTag(Tags::TagNaN);
        return log(-1);
    }
    public function readInfinityWithoutTag() {
        return (($this->stream->getc() === Tags::TagNeg) ? log(0) : -log(0));
    }
    public function readInfinity() {
        $this->checkTag(Tags::TagInfinity);
        return $this->readInfinityWithoutTag();
    }
    public function readNull() {
        $this->checkTag(Tags::TagNull);
        return null;
    }
    public function readEmpty() {
        $this->checkTag(Tags::TagEmpty);
        return '';
    }
    public function readBoolean() {
        $tag = $this->stream->getc();
        switch ($tag) {
            case Tags::TagTrue: return true;
            case Tags::TagFalse: return false;
            default: throw $this->unexpectedTag($tag);
        }
    }
    public function readDateWithoutTag() {
        $ymd = $this->stream->read(8);
        $hms = '000000';
        $u = '000000';
        $tag = $this->stream->getc();
        if ($tag == Tags::TagTime) {
            $hms = $this->stream->read(6);
            $tag = $this->stream->getc();
            if ($tag == Tags::TagPoint) {
                $u = $this->stream->read(3);
                $tag = $this->stream->getc();
                if (($tag >= '0') && ($tag <= '9')) {
                    $u .= $tag . $this->stream->read(2);
                    $tag = $this->stream->getc();
                    if (($tag >= '0') && ($tag <= '9')) {
                        $this->stream->skip(2);
                        $tag = $this->stream->getc();
                    }
                }
                else {
                    $u .= '000';
                }
            }
        }
        if ($tag == Tags::TagUTC) {
            $date = date_create_from_format('YmdHisu', $ymd.$hms.$u, timezone_open('UTC'));
        }
        else {
            $date = date_create_from_format('YmdHisu', $ymd.$hms.$u);
        }
        $this->refer->set($date);
        return $date;
    }
    public function readDate() {
        $tag = $this->stream->getc();
        switch ($tag) {
            case Tags::TagNull: return null;
            case Tags::TagDate: return $this->readDateWithoutTag();
            case Tags::TagRef: return $this->readRef();
            default: throw $this->unexpectedTag($tag);
        }
    }
    public function readTimeWithoutTag() {
        $hms = $this->stream->read(6);
        $u = '000000';
        $tag = $this->stream->getc();
        if ($tag == Tags::TagPoint) {
            $u = $this->stream->read(3);
            $tag = $this->stream->getc();
            if (($tag >= '0') && ($tag <= '9')) {
                $u .= $tag . $this->stream->read(2);
                $tag = $this->stream->getc();
                if (($tag >= '0') && ($tag <= '9')) {
                    $this->stream->skip(2);
                    $tag = $this->stream->getc();
                }
            }
            else {
                $u .= '000';
            }
        }
        if ($tag == Tags::TagUTC) {
            $time = date_create_from_format('!Hisu', $hms.$u, timezone_open('UTC'));
        }
        else {
            $time = date_create_from_format('!Hisu', $hms.$u);
        }
        $this->refer->set($time);
        return $time;
    }
    public function readTime() {
        $tag = $this->stream->getc();
        switch ($tag) {
            case Tags::TagNull: return null;
            case Tags::TagTime: return $this->readTimeWithoutTag();
            case Tags::TagRef: return $this->readRef();
            default: throw $this->unexpectedTag($tag);
        }
    }
    public function readBytesWithoutTag() {
        $count = (int)($this->stream->readuntil(Tags::TagQuote));
        $bytes = $this->stream->read($count);
        $this->stream->skip(1);
        $this->refer->set($bytes);
        return $bytes;
    }
    public function readBytes() {
        $tag = $this->stream->getc();
        switch ($tag) {
            case Tags::TagNull: return null;
            case Tags::TagEmpty: return '';
            case Tags::TagBytes: return $this->readBytesWithoutTag();
            case Tags::TagRef: return $this->readRef();
            default: throw $this->unexpectedTag($tag);
        }
    }
    public function readUTF8CharWithoutTag() {
        return $this->stream->readString(1);
    }
    public function readUTF8Char() {
        $this->checkTag(Tags::TagUTF8Char);
        return $this->readUTF8CharWithoutTag();
    }
    private function privateReadStringWithoutTag() {
        $len = (int)$this->stream->readuntil(Tags::TagQuote);
        $s = $this->stream->readString($len);
        $this->stream->skip(1);
        return $s;
    }
    public function readStringWithoutTag() {
        $s = $this->privateReadStringWithoutTag();
        $this->refer->set($s);
        return $s;
    }
    private function privateReadString() {
        $tag = $this->stream->getc();
        switch ($tag) {
            case Tags::TagUTF8Char: return $this->readUTF8CharWithoutTag();
            case Tags::TagString: return $this->readStringWithoutTag();
            case Tags::TagRef: return (string)$this->readRef();
            default: throw $this->unexpectedTag($tag);
        }
    }
    public function readString() {
        $tag = $this->stream->getc();
        switch ($tag) {
            case Tags::TagNull: return null;
            case Tags::TagEmpty: return '';
            case Tags::TagUTF8Char: return $this->readUTF8CharWithoutTag();
            case Tags::TagString: return $this->readStringWithoutTag();
            case Tags::TagRef: return $this->readRef();
            default: throw $this->unexpectedTag($tag);
        }
    }
    public function readGuidWithoutTag() {
        $this->stream->skip(1);
        $s = $this->stream->read(36);
        $this->stream->skip(1);
        $this->refer->set($s);
        return $s;
    }
    public function readGuid() {
        $tag = $this->stream->getc();
        switch ($tag) {
            case Tags::TagNull: return null;
            case Tags::TagGuid: return $this->readGuidWithoutTag();
            case Tags::TagRef: return $this->readRef();
            default: throw $this->unexpectedTag($tag);
        }
    }
    public function readListWithoutTag() {
        $list = array();
        $this->refer->set($list);
        $count = (int)$this->stream->readuntil(Tags::TagOpenbrace);
        for ($i = 0; $i < $count; ++$i) {
            $list[$i] = $this->unserialize();
        }
        $this->stream->skip(1);
        return $list;
    }
    public function readList() {
        $tag = $this->stream->getc();
        switch ($tag) {
            case Tags::TagNull: $result = null; return $result;
            case Tags::TagList: return $this->readListWithoutTag();
            case Tags::TagRef: return $this->readRef();
            default: throw $this->unexpectedTag($tag);
        }
    }
    public function readMapWithoutTag() {
        $map = array();
        $this->refer->set($map);
        $count = (int)$this->stream->readuntil(Tags::TagOpenbrace);
        for ($i = 0; $i < $count; ++$i) {
            $key = $this->unserializeKey();
            $map[$key] = $this->unserialize();
        }
        $this->stream->skip(1);
        return $map;
    }
    public function readMap() {
        $tag = $this->stream->getc();
        switch ($tag) {
            case Tags::TagNull: $result = null; return $result;
            case Tags::TagMap: return $this->readMapWithoutTag();
            case Tags::TagRef: return $this->readRef();
            default: throw $this->unexpectedTag($tag);
        }
    }
    public function readObjectWithoutTag() {
        $index = (int)$this->stream->readuntil(Tags::TagOpenbrace);
        list($classname, $props) = $this->classref[$index];
        if ($classname == 'stdClass') {
            $object = new stdClass();
            $this->refer->set($object);
            foreach ($props as $prop) {
                $object->$prop = $this->unserialize();
            }
        }
        else {
            $reflector = new ReflectionClass($classname);
            if ($reflector->getConstructor() === null) {
                if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
                    $object = $reflector->newInstanceWithoutConstructor();
                }
                else {
                    $object = new $classname();
                }
            }
            else {
                $object = $reflector->newInstance();
            }
            $this->refer->set($object);
            foreach ($props as $prop) {
                $value = $this->unserialize();
                if ($reflector->hasProperty($prop)) {
                    $property = $reflector->getProperty($prop);
                    $property->setAccessible(true);
                    $property->setValue($object, $value);
                }
                else {
                    $p = strtoupper($prop[0]) . substr($prop, 1);
                    if ($reflector->hasProperty($p)) {
                        $property = $reflector->getProperty($p);
                        $property->setAccessible(true);
                        $property->setValue($object, $value);
                    }
                    else {
                        $object->$prop = $value;
                    }
                }
            }
        }
        $this->stream->skip(1);
        return $object;
    }
    public function readObject() {
        $tag = $this->stream->getc();
        switch ($tag) {
            case Tags::TagNull: return null;
            case Tags::TagClass: $this->readclass(); return $this->readObject();
            case Tags::TagObject: return $this->readObjectWithoutTag();
            case Tags::TagRef: return $this->readRef();
            default: throw $this->unexpectedTag($tag);
        }
    }
    protected function readClass() {
        $classname = ClassManager::getClass($this->privateReadStringWithoutTag());
        $count = (int)$this->stream->readuntil(Tags::TagOpenbrace);
        $props = new SplFixedArray($count);
        for ($i = 0; $i < $count; ++$i) {
            $props[$i] = $this->privateReadString();
        }
        $this->stream->skip(1);
        $this->classref[] = array($classname, $props);
    }
    protected function readRef() {
        return $this->refer->read((int)$this->stream->readuntil(Tags::TagSemicolon));
    }
    public function reset() {
        $this->classref = array();
        $this->refer->reset();
    }
}

