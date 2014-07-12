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
 * HproseWriter.php                                       *
 *                                                        *
 * hprose writer class for php5.                          *
 *                                                        *
 * LastModified: Jul 12, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (!extension_loaded('hprose')) {

require_once('HproseCommon.php');
require_once('HproseTags.php');
require_once('HproseClassManager.php');

interface HproseWriterRefer {
    public function set(&$val);
    public function write($stream, &$val);
    public function reset();
}

class HproseFakeWriterRefer implements HproseWriterRefer {
    public function set(&$val) {}
    public function write($stream, &$val) { return false; }
    public function reset() {}
}

class HproseRealWriterRefer implements HproseWriterRefer {
    private $ref;
    private $arrayref;
    private $refcount;
    function __construct() {
        $this->reset();
    }
    private function getKey(&$obj) {
        if (is_string($obj)) {
            $key = 's_' . $obj;
        }
        elseif ($obj instanceof HproseBytes) {
            $key = 'b_' . $obj->value;
        }
        elseif (is_array($obj)) {
            if (($i = array_ref_search($obj, $this->arrayref)) === false) {
                $i = count($this->arrayref);
                $this->arrayref[$i] = &$obj;
            }
            $key = 'a_' . $i;
        }
        elseif ($obj instanceof HproseMap) {
            if (($i = array_ref_search($obj->value, $this->arrayref)) === false) {
                $i = count($this->arrayref);
                $this->arrayref[$i] = &$obj->value;
            }
            $key = 'm_' . $i;
        }
        else {
            $key = 'o_' . spl_object_hash($obj);
        }
        return $key;
    }
    public function set(&$val) {
        $this->ref[$this->getKey($val)] = $this->refcount++;
    }
    public function write($stream, &$val) {
        $key = $this->getKey($val);
        if (array_key_exists($key, $this->ref)) {
            $stream->write(HproseTags::TagRef . $this->ref[$key] . HproseTags::TagSemicolon);
            return true;
        }
        return false;
    }
    public function reset() {
        $this->ref = array();
        $this->arrayref = array();
        $this->refcount = 0;
    }
}

class HproseWriter {
    public $stream;
    private $classref;
    private $fieldsref;
    private $refer;
    function __construct(&$stream, $simple = false) {
        $this->stream = &$stream;
        $this->classref = array();
        $this->fieldsref = array();
        $this->refer = $simple ? new HproseFakeWriterRefer() : new HproseRealWriterRefer();
    }
    public function serialize(&$var) {
        if ((!isset($var)) || ($var === NULL)) {
            $this->writeNull();
        }
        elseif (is_scalar($var)) {
            if (is_int($var)) {
                if ($var >= 0 && $var <= 9) {
                    $this->stream->write((string)$var);
                }
                else {
                    $this->writeInteger($var);
                }
            }
            elseif (is_bool($var)) {
                $this->writeBoolean($var);
            }
            elseif (is_float($var)) {
                $this->writeDouble($var);
            }
            elseif (is_string($var)) {
                if ($var === '') {
                    $this->writeEmpty();
                }
                elseif ((strlen($var) < 4) && is_utf8($var) && (ustrlen($var) == 1)) {
                    $this->writeUTF8Char($var);
                }
                elseif (is_utf8($var)) {
                    $this->writeStringWithRef($var);
                }
                else {
                    $this->writeBytesWithRef(bytes($var));
                }
            }
        }
        elseif (is_array($var)) {
            if (is_list($var)) {
                $this->writeListWithRef($var);
            }
            else {
                $m = map($var);
                $this->writeMapWithRef($m);
            }
        }
        elseif (is_object($var)) {
            if ($var instanceof stdClass) {
                $this->writeMapWithRef($var);
            }
            elseif ($var instanceof DateTime) {
                $this->writeDateTimeWithRef($var);
            }
            elseif (($var instanceof HproseDate) || ($var instanceof HproseDateTime)) {
                $this->writeDateWithRef($var);
            }
            elseif ($var instanceof HproseTime) {
                $this->writeTimeWithRef($var);
            }
            elseif ($var instanceof HproseBytes) {
                $this->writeBytesWithRef($var);
            }
            elseif ($var instanceof HproseMap) {
                $this->writeMapWithRef($var);
            }
            else {
                $this->writeObjectWithRef($var);
            }
        }
        else {
            throw new Exception('Not support to serialize this data');
        }
    }
    public function writeInteger($integer) {
        $this->stream->write(HproseTags::TagInteger . $integer . HproseTags::TagSemicolon);
    }
    public function writeLong($long) {
        $this->stream->write(HproseTags::TagLong . $long . HproseTags::TagSemicolon);
    }
    public function writeDouble($double) {
        if (is_nan($double)) {
            $this->writeNaN();
        }
        elseif (is_infinite($double)) {
            $this->writeInfinity($double > 0);
        }
        else {
            $this->stream->write(HproseTags::TagDouble . $double . HproseTags::TagSemicolon);
        }
    }
    public function writeNaN() {
        $this->stream->write(HproseTags::TagNaN);
    }
    public function writeInfinity($positive = true) {
        $this->stream->write(HproseTags::TagInfinity . ($positive ? HproseTags::TagPos : HproseTags::TagNeg));
    }
    public function writeNull() {
        $this->stream->write(HproseTags::TagNull);
    }
    public function writeEmpty() {
        $this->stream->write(HproseTags::TagEmpty);
    }
    public function writeBoolean($bool) {
        $this->stream->write($bool ? HproseTags::TagTrue : HproseTags::TagFalse);
    }
    public function writeDateTime($datetime) {
        $this->refer->set($datetime);
        if ($datetime->getOffset() == 0) {
            $this->stream->write($datetime->format("\\DYmd\\THis.u\\Z"));
        }
        else {
            $this->stream->write($datetime->format("\\DYmd\\THis.u;"));
        }
    }
    public function writeDateTimeWithRef($datetime) {
        if (!$this->refer->write($this->stream, $datetime)) $this->writeDate($datetime);
    }
    public function writeDate($date) {
        $this->refer->set($date);
        if ($date->utc) {
            $this->stream->write(HproseTags::TagDate . $date->toString(false));
        }
        else {
            $this->stream->write(HproseTags::TagDate . $date->toString(false) . HproseTags::TagSemicolon);
        }
    }
    public function writeDateWithRef($date) {
        if (!$this->refer->write($this->stream, $date)) $this->writeDate($date);
    }
    public function writeTime($time) {
        $this->refer->set($time);
        if ($time->utc) {
            $this->stream->write(HproseTags::TagTime . $time->toString(false));
        }
        else {
            $this->stream->write(HproseTags::TagTime . $time->toString(false) . HproseTags::TagSemicolon);
        }
    }
    public function writeTimeWithRef($time) {
        if (!$this->refer->write($this->stream, $time)) $this->writeTime($time);
    }
    public function writeBytes($bytes) {
        $this->refer->set($bytes);
        if ($bytes instanceof HproseBytes) $bytes = $bytes->value;
        $len = strlen($bytes);
        $this->stream->write(HproseTags::TagBytes);
        if ($len > 0) $this->stream->write((string)$len);
        $this->stream->write(HproseTags::TagQuote . $bytes . HproseTags::TagQuote);
    }
    public function writeBytesWithRef($bytes) {
        if (!$this->refer->write($this->stream, $bytes)) $this->writeBytes($bytes);
    }
    public function writeUTF8Char($char) {
        $this->stream->write(HproseTags::TagUTF8Char . $char);
    }
    public function writeString($str) {
        $this->refer->set($str);
        $len = ustrlen($str);
        $this->stream->write(HproseTags::TagString);
        if ($len > 0) $this->stream->write((string)$len);
        $this->stream->write(HproseTags::TagQuote . $str . HproseTags::TagQuote);
    }
    public function writeStringWithRef($str) {
        if (!$this->refer->write($this->stream, $str)) $this->writeString($str);
    }
    public function writeList(&$list) {
        $this->refer->set($list);
        $count = count($list);
        $this->stream->write(HproseTags::TagList);
        if ($count > 0) $this->stream->write((string)$count);
        $this->stream->write(HproseTags::TagOpenbrace);
        for ($i = 0; $i < $count; ++$i) {
            $this->serialize($list[$i]);
        }
        $this->stream->write(HproseTags::TagClosebrace);
    }
    public function writeListWithRef(&$list) {
        if (!$this->refer->write($this->stream, $list)) $this->writeList($list);
    }
    public function writeMap(&$map) {
        $this->refer->set($map);
        if ($map instanceof HproseMap) {
            $m = &$map->value;
        }
        elseif ($map instanceof stdClass) {
            $m = (array)$map;
        } else {
            $m = $map;
        }
        $count = count($m);
        $this->stream->write(HproseTags::TagMap);
        if ($count > 0) $this->stream->write((string)$count);
        $this->stream->write(HproseTags::TagOpenbrace);
        foreach ($m as $key => &$value) {
            $this->serialize($key);
            $this->serialize($value);
        }
        $this->stream->write(HproseTags::TagClosebrace);
    }
    public function writeMapWithRef(&$map) {
        if (!$this->refer->write($this->stream, $map)) $this->writeMap($map);
    }
    public function writeObject($obj) {
        $class = get_class($obj);
        $alias = HproseClassManager::getClassAlias($class);
        $fields = array_keys((array)$obj);
        if (array_key_exists($alias, $this->classref)) {
            $index = $this->classref[$alias];
        }
        else {
            $index = $this->writeClass($alias, $fields);
        }
        $this->refer->set($obj);
        $fields = $this->fieldsref[$index];
        $count = count($fields);
        $this->stream->write(HproseTags::TagObject . $index . HproseTags::TagOpenbrace);
        $array = (array)$obj;
        for ($i = 0; $i < $count; ++$i) {
            $this->serialize($array[$fields[$i]]);
        }
        $this->stream->write(HproseTags::TagClosebrace);
    }
    public function writeObjectWithRef($obj) {
        if (!$this->refer->write($this->stream, $obj)) $this->writeObject($obj);
    }
    protected function writeClass($alias, $fields) {
        $len = ustrlen($alias);
        $this->stream->write(HproseTags::TagClass . $len .
                             HproseTags::TagQuote . $alias . HproseTags::TagQuote);
        $count = count($fields);
        if ($count > 0) $this->stream->write((string)$count);
        $this->stream->write(HproseTags::TagOpenbrace);
        for ($i = 0; $i < $count; ++$i) {
            $field = $fields[$i];
            if ($field{0} === "\0") {
                $field = substr($field, strpos($field, "\0", 1) + 1);
            }
            $this->writeString($field);
        }
        $this->stream->write(HproseTags::TagClosebrace);
        $index = count($this->fieldsref);
        $this->classref[$alias] = $index;
        $this->fieldsref[$index] = $fields;
        return $index;
    }
    public function reset() {
        $this->classref = array();
        $this->fieldsref = array();
        $this->refer->reset();
    }
}

} // endif (!extension_loaded('hprose'))
?>