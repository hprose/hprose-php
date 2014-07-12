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
 * HproseSerialize.php                                    *
 *                                                        *
 * hprose serialize library for php5.                     *
 *                                                        *
 * LastModified: Jul 12, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (!extension_loaded('hprose')) {

require_once('HproseCommon.php');
require_once('HproseClassManager.php');

// private functions

function hprose_hash(&$v, $ro) {
    if (is_string($v)) {
        return 's_' . $v;
    }
    if ($v instanceof HproseBytes) {
        return 'b_' . $v->value;
    }
    if (is_array($v)) {
        if (($i = array_ref_search($v, $ro->ar)) === false) {
            $i = count($ro->ar);
            $ro->ar[$i] = &$v;
        }
        return 'a_' . $i;
    }
    if ($v instanceof HproseMap) {
        if (($i = array_ref_search($v->value, $ro->ar)) === false) {
            $i = count($ro->ar);
            $ro->ar[$i] = &$v->value;
        }
        return 'm_' . $i;
    }
    return 'o_' . spl_object_hash($v);
}

function hprose_simple_serialize(&$v, $ro) {
    if ($v === NULL) {
        return 'n';
    }
    if (is_int($v)) {
        if ($v >= 0 && $v <= 9) {
            return (string)$v;
        }
        return 'i' . $v . ';';
    }
    if (is_bool($v)) {
        return $v ? 't' : 'f';
    }
    if (is_float($v)) {
        if (is_nan($v)) {
            return 'N';
        }
        if (is_infinite($v)) {
            return $v > 0 ? 'I+' : 'I-';
        }
        return 'd' . $v . ';';
    }
    if (is_string($v)) {
        if ($v === '') {
            return 'e';
        }
        if (is_utf8($v)) {
            $l = ustrlen($v);
            if ($l == 1) {
                return 'u' . $v;
            }
            return 's' . $l . '"' . $v . '"';
        }
        return 'b' . strlen($v) . '"' . $v . '"';
    }
    if (is_array($v)) {
        $c = count($v);
        if ($c == 0) return 'a{}';
        if (is_list($v)) {
            $s = 'a' . $c . '{';
            foreach ($v as &$val) {
                $s .= hprose_simple_serialize($val, $ro);
            }
            return $s . '}';
        }
        $s = 'm' . $c . '{';
        foreach ($v as $key => &$val) {
            $s .= hprose_simple_serialize($key, $ro) .
                  hprose_simple_serialize($val, $ro);
        }
        return $s . '}';
    }
    if (is_object($v)) {
        if ($v instanceof stdClass) {
            $v = (array)$v;
            $c = count($v);
            if ($c == 0) return 'a{}';
            $s = 'm' . $c . '{';
            foreach ($v as $key => &$val) {
                $s .= hprose_simple_serialize($key, $ro) .
                      hprose_simple_serialize($val, $ro);
            }
            return $s . '}';
        }
        if ($v instanceof DateTime) {
            if ($v->getOffset() == 0) {
                return $v->format("\\DYmd\\THis.u\\Z");
            }
            return $v->format("\\DYmd\\THis.u;");
        }
        if (($v instanceof HproseDate) || ($v instanceof HproseDateTime)) {
            if ($v->utc) {
                return 'D' . $v->toString(false);
            }
            return 'D' . $v->toString(false) . 'Z';
        }
        if ($v instanceof HproseTime) {
            if ($v->utc) {
                return 'T' . $v->toString(false);
            }
            return 'T' . $v->toString(false) . 'Z';
        }
        if ($v instanceof HproseBytes) {
            $c = strlen($v->value);
            if ($c == 0) return 'b""';
            return 'b' . $c . '"' . $v->value . '"';
        }
        if ($v instanceof HproseMap) {
            $c = count($v->value);
            if ($c == 0) return 'a{}';
            $s = 'm' . $c . '{';
            foreach ($v->value as $key => &$val) {
                $s .= hprose_simple_serialize($key, $ro) .
                      hprose_simple_serialize($val, $ro);
            }
            return $s . '}';
        }
        $class = get_class($v);
        $alias = HproseClassManager::getClassAlias($class);
        $a = (array)$v;
        if (array_key_exists($alias, $ro->cr)) {
            $index = $ro->cr[$alias];
            $fields = $ro->fr[$index];
            $c = count($fields);
            $s = '';
        }
        else {
            $l = ustrlen($alias);
            $s = 'c' . $l . '"' . $alias . '"';
            $fields = array_keys($a);
            $c = count($fields);
            if ($c > 0) {
                $s .= $c . '{';
                foreach ($fields as $field) {
                    if ($field[0] === "\0") {
                        $field = substr($field, strpos($field, "\0", 1) + 1);
                    }
                    $s .= 's' . ustrlen($field) . '"' . $field . '"';
                }
                $s .= '}';
            }
            else {
                $s .= '{}';
            }
            $index = count($ro->fr);
            $ro->cr[$alias] = $index;
            $ro->fr[$index] = $fields;
        }
        $s .= 'o' . $index . '{';
        for ($i = 0; $i < $c; ++$i) {
            $s .= hprose_simple_serialize($a[$fields[$i]], $ro);
        }
        return $s . '}';
    }
    throw new Exception('Not support to serialize this data');
}

function hprose_fast_serialize(&$v, $ro) {
    if ($v === NULL) {
        return 'n';
    }
    if (is_int($v)) {
        if ($v >= 0 && $v <= 9) {
            return (string)$v;
        }
        return 'i' . $v . ';';
    }
    if (is_bool($v)) {
        return $v ? 't' : 'f';
    }
    if (is_float($v)) {
        if (is_nan($v)) {
            return 'N';
        }
        if (is_infinite($v)) {
            return $v > 0 ? 'I+' : 'I-';
        }
        return 'd' . $v . ';';
    }
    if (is_string($v)) {
        if ($v === '') {
            return 'e';
        }
        if (is_utf8($v)) {
            $l = ustrlen($v);
            if ($l == 1) {
                return 'u' . $v;
            }
            $h = hprose_hash($v, $ro);
            if (array_key_exists($h, $ro->r)) {
                return 'r' . $ro->r[$h] . ';';
            }
            $ro->r[$h] = $ro->length++;
            return 's' . $l . '"' . $v . '"';
        }
        $h = hprose_hash($v, $ro);
        if (array_key_exists($h, $ro->r)) {
            return 'r' . $ro->r[$h] . ';';
        }
        $ro->r[$h] = $ro->length++;
        return 'b' . strlen($v) . '"' . $v . '"';
    }
    if (is_array($v)) {
        $c = count($v);
        if (is_list($v)) {
            $h = hprose_hash($v, $ro);
            if (array_key_exists($h, $ro->r)) {
                return 'r' . $ro->r[$h] . ';';
            }
            $ro->r[$h] = $ro->length++;
            if ($c == 0) return 'a{}';
            $s = 'a' . $c . '{';
            foreach ($v as &$val) {
                $s .= hprose_fast_serialize($val, $ro);
            }
            return $s . '}';
        }
        $h = hprose_hash(map($v), $ro);
        if (array_key_exists($h, $ro->r)) {
            return 'r' . $ro->r[$h] . ';';
        }
        $ro->r[$h] = $ro->length++;
        $s = 'm' . $c . '{';
        foreach ($v as $key => &$val) {
            $s .= hprose_fast_serialize($key, $ro) .
                  hprose_fast_serialize($val, $ro);
        }
        return $s . '}';
    }
    if (is_object($v)) {
        $h = hprose_hash($v, $ro);
        if (array_key_exists($h, $ro->r)) {
            return 'r' . $ro->r[$h] . ';';
        }
        if ($v instanceof stdClass) {
            $ro->r[$h] = $ro->length++;
            $v = (array)$v;
            $c = count($v);
            if ($c == 0) return 'a{}';
            $s = 'm' . $c . '{';
            foreach ($v as $key => &$val) {
                $s .= hprose_fast_serialize($key, $ro) .
                      hprose_fast_serialize($val, $ro);
            }
            return $s . '}';
        }
        if ($v instanceof DateTime) {
            $ro->r[$h] = $ro->length++;
            if ($v->getOffset() == 0) {
                return $v->format("\\DYmd\\THis.u\\Z");
            }
            return $v->format("\\DYmd\\THis.u;");
        }
        if (($v instanceof HproseDate) || ($v instanceof HproseDateTime)) {
            $ro->r[$h] = $ro->length++;
            if ($v->utc) {
                return 'D' . $v->toString(false);
            }
            return 'D' . $v->toString(false) . 'Z';
        }
        if ($v instanceof HproseTime) {
            $ro->r[$h] = $ro->length++;
            if ($v->utc) {
                return 'T' . $v->toString(false);
            }
            return 'T' . $v->toString(false) . 'Z';
        }
        if ($v instanceof HproseBytes) {
            $ro->r[$h] = $ro->length++;
            $c = strlen($v->value);
            if ($c == 0) return 'b""';
            return 'b' . $c . '"' . $v->value . '"';
        }
        if ($v instanceof HproseMap) {
            $ro->r[$h] = $ro->length++;
            $c = count($v->value);
            if ($c == 0) return 'a{}';
            $s = 'm' . $c . '{';
            foreach ($v->value as $key => &$val) {
                $s .= hprose_fast_serialize($key, $ro) .
                      hprose_fast_serialize($val, $ro);
            }
            return $s . '}';
        }
        $class = get_class($v);
        $alias = HproseClassManager::getClassAlias($class);
        $a = (array)$v;
        if (array_key_exists($alias, $ro->cr)) {
            $index = $ro->cr[$alias];
            $fields = $ro->fr[$index];
            $c = count($fields);
            $s = '';
        }
        else {
            $l = ustrlen($alias);
            $s = 'c' . $l . '"' . $alias . '"';
            $fields = array_keys($a);
            $c = count($fields);
            if ($c > 0) {
                $s .= $c . '{';
                foreach ($fields as $field) {
                    if ($field[0] === "\0") {
                        $field = substr($field, strpos($field, "\0", 1) + 1);
                    }
                    $fh = hprose_hash($field, $ro);
                    $ro->r[$fh] = $ro->length++;
                    $s .= 's' . ustrlen($field) . '"' . $field . '"';
                }
                $s .= '}';
            }
            else {
                $s .= '{}';
            }
            $index = count($ro->fr);
            $ro->cr[$alias] = $index;
            $ro->fr[$index] = $fields;
        }
        $ro->r[$h] = $ro->length++;
        $s .= 'o' . $index . '{';
        for ($i = 0; $i < $c; ++$i) {
            $s .= hprose_fast_serialize($a[$fields[$i]], $ro);
        }
        return $s . '}';
    }
    throw new Exception('Not support to serialize this data');
}

// public functions

function hprose_serialize_bool($b) {
    return $b ? 't' : 'f';
}

function hprose_serialize_string($s) {
    return 's' . ustrlen($s) . '"' . $s . '"';
}

function hprose_serialize_list(&$a, $simple = false) {
    $c = count($a);
    if ($c == 0) return 'a{}';
    $ro = new stdClass();
    $ro->cr = array();
    $ro->fr = array();
    if ($simple) {
        $s = 'a' . $c . '{';
        foreach ($a as &$v) {
            $s .= hprose_simple_serialize($v, $ro);
        }
        return $s . '}';
    }
    $ro->ar = array();
    $ro->r = array();
    $ro->length = 0;
    $h = hprose_hash($a, $ro);
    if (array_key_exists($h, $ro->r)) {
        return 'r' . $ro->r[$h] . ';';
    }
    $ro->r[$h] = $ro->length++;
    $s = 'a' . $c . '{';
    foreach ($a as &$v) {
        $s .= hprose_fast_serialize($v, $ro);
    }
    return $s . '}';
}

function hprose_serialize(&$v, $simple = false) {
    $ro = new stdClass();
    $ro->cr = array();
    $ro->fr = array();
    if ($simple) {
        return hprose_simple_serialize($v, $ro);
    }
    $ro->ar = array();
    $ro->r = array();
    $ro->length = 0;
    return hprose_fast_serialize($v, $ro);
}

} // endif (!extension_loaded('hprose'))
?>