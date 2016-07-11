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
 * Hprose/Writer.php                                      *
 *                                                        *
 * hprose writer class for php 5.3+                       *
 *                                                        *
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

use stdClass;
use DateTime;
use Exception;
use ReflectionClass;
use ReflectionProperty;
use SplObjectStorage;
use Traversable;

class Writer {
    public $stream;
    private $classref = array();
    private $propsref = array();
    private $refer;
    public function __construct(BytesIO $stream, $simple = false) {
        $this->stream = $stream;
        $this->refer = $simple ?
                       new FakeWriterRefer() :
                       new RealWriterRefer();
    }
    private static function isUTF8($s) {
        return mb_detect_encoding($s, 'UTF-8', true) !== false;
    }
    private static function ustrlen($s) {
        return strlen(iconv('UTF-8', 'UTF-16LE', $s)) >> 1;
    }
    private static function isList(array $a) {
        $count = count($a);
        return ($count === 0) ||
               ((isset($a[0]) || array_key_exists(0, $a)) && (($count === 1) ||
               (isset($a[$count - 1]) || array_key_exists($count - 1, $a))));
    }
    public function serialize($val) {
        if ($val === null) {
            $this->writeNull();
        }
        elseif (is_scalar($val)) {
            if (is_int($val)) {
                if ($val >= 0 && $val <= 9) {
                    $this->stream->write((string)$val);
                }
                elseif ($val >= -2147483648 && $val <= 2147483647) {
                    $this->writeInteger($val);
                }
                else {
                    $this->writeLong((string)$val);
                }
            }
            elseif (is_bool($val)) {
                $this->writeBoolean($val);
            }
            elseif (is_float($val)) {
                $this->writeDouble($val);
            }
            elseif (is_string($val)) {
                if ($val === '') {
                    $this->writeEmpty();
                }
                elseif (strlen($val) < 4 &&
                    self::isUTF8($val) &&
                    self::ustrlen($val) == 1) {
                    $this->writeUTF8Char($val);
                }
                elseif (self::isUTF8($val)) {
                    $this->writeStringWithRef($val);
                }
                else {
                    $this->writeBytesWithRef($val);
                }
            }
        }
        elseif (is_array($val)) {
            if (self::isList($val)) {
                $this->writeArray($val);
            }
            else {
                $this->writeAssocArray($val);
            }
        }
        elseif (is_object($val)) {
            if ($val instanceof BytesIO) {
                $this->writeBytesIOWithRef($val);
            }
            elseif ($val instanceof DateTime) {
                $this->writeDateTimeWithRef($val);
            }
            elseif ($val instanceof SplObjectStorage) {
                $this->writeMapWithRef($val);
            }
            elseif ($val instanceof Traversable) {
                $this->writeListWithRef($val);
            }
            elseif ($val instanceof stdClass) {
                $this->writeStdClassWithRef($val);
            }
            else {
                $this->writeObjectWithRef($val);
            }
        }
        else {
            throw new Exception('Not support to serialize this data');
        }
    }
    public function writeInteger($int) {
        $this->stream->write(Tags::TagInteger . $int . Tags::TagSemicolon);
    }
    public function writeLong($long) {
        $this->stream->write(Tags::TagLong . $long . Tags::TagSemicolon);
    }
    public function writeDouble($double) {
        if (is_nan($double)) {
            $this->writeNaN();
        }
        elseif (is_infinite($double)) {
            $this->writeInfinity($double > 0);
        }
        else {
            $this->stream->write(Tags::TagDouble . $double . Tags::TagSemicolon);
        }
    }
    public function writeNaN() {
        $this->stream->write(Tags::TagNaN);
    }
    public function writeInfinity($positive = true) {
        $this->stream->write(Tags::TagInfinity . ($positive ? Tags::TagPos : Tags::TagNeg));
    }
    public function writeNull() {
        $this->stream->write(Tags::TagNull);
    }
    public function writeEmpty() {
        $this->stream->write(Tags::TagEmpty);
    }
    public function writeBoolean($bool) {
        $this->stream->write($bool ? Tags::TagTrue : Tags::TagFalse);
    }
    public function writeUTF8Char($char) {
        $this->stream->write(Tags::TagUTF8Char . $char);
    }
    public function writeString($str) {
        $this->refer->set($str);
        $len = self::ustrlen($str);
        $this->stream->write(Tags::TagString);
        if ($len > 0) {
            $this->stream->write((string)$len);
        }
        $this->stream->write(Tags::TagQuote . $str . Tags::TagQuote);
    }
    public function writeStringWithRef($str) {
        if (!$this->refer->write($this->stream, $str)) {
            $this->writeString($str);
        }
    }
    public function writeBytes($bytes) {
        $this->refer->set($bytes);
        $len = strlen($bytes);
        $this->stream->write(Tags::TagBytes);
        if ($len > 0) {
            $this->stream->write((string)$len);
        }
        $this->stream->write(Tags::TagQuote . $bytes . Tags::TagQuote);
    }
    public function writeBytesWithRef($bytes) {
        if (!$this->refer->write($this->stream, $bytes)) {
            $this->writeBytes($bytes);
        }
    }
    public function writeBytesIO(BytesIO $bytes) {
        $this->refer->set($bytes);
        $len = $bytes->length();
        $this->stream->write(Tags::TagBytes);
        if ($len > 0) {
            $this->stream->write((string)$len);
        }
        $this->stream->write(Tags::TagQuote . $bytes->toString() . Tags::TagQuote);
    }
    public function writeBytesIOWithRef(BytesIO $bytes) {
        if (!$this->refer->write($this->stream, $bytes)) {
            $this->writeBytesIO($bytes);
        }
    }
    public function writeDateTime(DateTime $datetime) {
        $this->refer->set($datetime);
        if ($datetime->getOffset() == 0) {
            $this->stream->write($datetime->format('\DYmd\THis.u\Z'));
        }
        else {
            $this->stream->write($datetime->format('\DYmd\THis.u;'));
        }
    }
    public function writeDateTimeWithRef(DateTime $datetime) {
        if (!$this->refer->write($this->stream, $datetime)) {
            $this->writeDateTime($datetime);
        }
    }
    public function writeArray(array $array) {
        $this->refer->set($array);
        $count = count($array);
        $this->stream->write(Tags::TagList);
        if ($count > 0) {
            $this->stream->write((string)$count);
        }
        $this->stream->write(Tags::TagOpenbrace);
        for ($i = 0; $i < $count; $i++) {
            $this->serialize($array[$i]);
        }
        $this->stream->write(Tags::TagClosebrace);
    }
    public function writeAssocArray(array $map) {
        $this->refer->set($map);
        $count = count($map);
        $this->stream->write(Tags::TagMap);
        if ($count > 0) {
            $this->stream->write((string)$count);
        }
        $this->stream->write(Tags::TagOpenbrace);
        foreach ($map as $key => $value) {
            $this->serialize($key);
            $this->serialize($value);
        }
        $this->stream->write(Tags::TagClosebrace);
    }
    public function writeList(Traversable $list) {
        $this->refer->set($list);
        $count = count($list);
        $this->stream->write(Tags::TagList);
        if ($count > 0) {
            $this->stream->write((string)$count);
        }
        $this->stream->write(Tags::TagOpenbrace);
        foreach ($list as $e) {
            $this->serialize($e);
        }
        $this->stream->write(Tags::TagClosebrace);
    }
    public function writeListWithRef(Traversable $list) {
        if (!$this->refer->write($this->stream, $list)) {
            $this->writeList($list);
        }
    }
    public function writeMap(SplObjectStorage $map) {
        $this->refer->set($map);
        $count = count($map);
        $this->stream->write(Tags::TagMap);
        if ($count > 0) {
            $this->stream->write((string)$count);
        }
        $this->stream->write(Tags::TagOpenbrace);
        foreach ($map as $o) {
            $this->serialize($o);
            $this->serialize($map[$o]);
        }
        $this->stream->write(Tags::TagClosebrace);
    }
    public function writeMapWithRef(SplObjectStorage $map) {
        if (!$this->refer->write($this->stream, $map)) {
            $this->writeMap($map);
        }
    }
    public function writeStdClass(stdClass $obj) {
        $this->refer->set($obj);
        $vars = get_object_vars($obj);
        $count = count($vars);
        $this->stream->write(Tags::TagMap);
        if ($count > 0) {
            $this->stream->write((string)$count);
        }
        $this->stream->write(Tags::TagOpenbrace);
        foreach ($vars as $key => $value) {
            $this->serialize($key);
            $this->serialize($value);
        }
        $this->stream->write(Tags::TagClosebrace);
    }
    public function writeStdClassWithRef(stdClass $obj) {
        if (!$this->refer->write($this->stream, $obj)) {
            $this->writeStdClass($obj);
        }
    }
    public function writeObject($obj) {
        $class = get_class($obj);
        $alias = ClassManager::getClassAlias($class);
        if (isset($this->classref[$alias])) {
            $index = $this->classref[$alias];
        }
        else {
            $reflector = new ReflectionClass($obj);
            $props = $reflector->getProperties(
                ReflectionProperty::IS_PUBLIC |
                ReflectionProperty::IS_PROTECTED |
                ReflectionProperty::IS_PRIVATE);
            $index = $this->writeClass($alias, $props);
        }
        $this->refer->set($obj);
        $props = $this->propsref[$index];
        $this->stream->write(Tags::TagObject . $index . Tags::TagOpenbrace);
        foreach ($props as $prop) {
            $this->serialize($prop->getValue($obj));
        }
        $this->stream->write(Tags::TagClosebrace);
    }
    public function writeObjectWithRef($obj) {
        if (!$this->refer->write($this->stream, $obj)) {
            $this->writeObject($obj);
        }
    }
    protected function writeClass($alias, array $props) {
        $len = self::ustrlen($alias);
        $this->stream->write(Tags::TagClass . $len .
                             Tags::TagQuote . $alias . Tags::TagQuote);
        $count = count($props);
        if ($count > 0) {
            $this->stream->write((string)$count);
        }
        $this->stream->write(Tags::TagOpenbrace);
        foreach ($props as $prop) {
            $prop->setAccessible(true);
            $name = $prop->getName();
            $fl = ord($name[0]);
            if ($fl >= ord('A') && $fl <= ord('Z')) {
                $name = strtolower($name[0]) . substr($name, 1);
            }
            $this->writeString($name);
        }
        $this->stream->write(Tags::TagClosebrace);
        $index = count($this->propsref);
        $this->classref[$alias] = $index;
        $this->propsref[] = $props;
        return $index;
    }
    public function reset() {
        $this->classref = array();
        $this->propsref = array();
        $this->refer->reset();
    }
}
