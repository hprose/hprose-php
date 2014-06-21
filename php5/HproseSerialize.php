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
 * LastModified: Jun 20, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

require_once('HproseCommon.php');
require_once('HproseClassManager.php');

function hprose_hash(&$v, &$ar) {
    if (is_string($v)) {
        return 's_' . $v;
    }
    if ($v instanceof HproseBytes) {
        return 'b_' . $v->value;
    }
    if (is_array($v)) {
        if (($i = array_ref_search($v, $ar)) === false) {
            $i = count($ar);
            $ar[$i] = &$v;
        }
        return 'a_' . $i;
    }
    if ($v instanceof HproseMap) {
        if (($i = array_ref_search($v->value, $ar)) === false) {
            $i = count($ar);
            $ar[$i] = &$v->value;
        }
        return 'm_' . $i;
    }
    return 'o_' . spl_object_hash($v);
}

function hprose_serialize(&$v, $simple = false) {
    $cr = array();
    $fr = array();
    if ($simple) {
        return hprose_simple_serialize($v, $cr, $fr);
    }
    $ar = array();
    $r = array("length" => 0);
    return hprose_fast_serialize($v, $cr, $fr, $ar, $r);
}

function hprose_simple_serialize(&$v, &$cr, &$fr) {
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
            for ($i = 0; $i < $c; ++$i) {
                $s .= hprose_simple_serialize($v[$i], $cr, $fr);
            }
            return $s . '}';
        }
        $s = 'm' . $c . '{';
        foreach ($v as $key => &$value) {
            $s .= hprose_simple_serialize($key, $cr, $fr) .
                  hprose_simple_serialize($value, $cr, $fr);
        }
        return $s . '}';
    }
    if (is_object($v)) {
        if ($v instanceof stdClass) {
            $v = (array)$v;
            $c = count($v);
            if ($c == 0) return 'a{}';
            $s = 'm' . $c . '{';
            foreach ($v as $key => &$value) {
                $s .= hprose_simple_serialize($key, $cr, $fr) .
                      hprose_simple_serialize($value, $cr, $fr);
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
            foreach ($v->value as $key => &$value) {
                $s .= hprose_simple_serialize($key, $cr, $fr) .
                      hprose_simple_serialize($value, $cr, $fr);
            }
            return $s . '}';
        }
        $class = get_class($v);
        $alias = HproseClassManager::getClassAlias($class);
        $a = (array)$v;
        if (array_key_exists($alias, $cr)) {
            $index = $cr[$alias];
            $fields = $fr[$index];
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
                for ($i = 0; $i < $c; ++$i) {
                    $field = $fields[$i];
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
            $index = count($fr);
            $cr[$alias] = $index;
            $fr[$index] = $fields;
        }
        $s .= 'o' . $index . '{';
        for ($i = 0; $i < $c; ++$i) {
            $s .= hprose_simple_serialize($a[$fields[$i]], $cr, $fr);
        }
        return $s . '}';
    }
    throw new HproseException('Not support to serialize this data');
}

function hprose_fast_serialize(&$v, &$cr, &$fr, &$ar, &$r) {
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
            $h = hprose_hash($v, $ar);
            if (array_key_exists($h, $r)) {
                return 'r' . $r[$h] . ';';
            }
            $r[$h] = $r["length"]++;
            return 's' . $l . '"' . $v . '"';
        }
        $h = hprose_hash($v, $ar);
        if (array_key_exists($h, $r)) {
            return 'r' . $r[$h] . ';';
        }
        $r[$h] = $r["length"]++;
        return 'b' . strlen($v) . '"' . $v . '"';
    }
    if (is_array($v)) {
        $c = count($v);
        if (is_list($v)) {
            $h = hprose_hash($v, $ar);
            if (array_key_exists($h, $r)) {
                return 'r' . $r[$h] . ';';
            }
            $r[$h] = $r["length"]++;
            if ($c == 0) return 'a{}';
            $s = 'a' . $c . '{';
            for ($i = 0; $i < $c; ++$i) {
                $s .= hprose_fast_serialize($v[$i], $cr, $fr, $ar, $r);
            }
            return $s . '}';
        }
        $h = hprose_hash(map($v), $ar);
        if (array_key_exists($h, $r)) {
            return 'r' . $r[$h] . ';';
        }
        $r[$h] = $r["length"]++;
        $s = 'm' . $c . '{';
        foreach ($v as $key => &$value) {
            $s .= hprose_fast_serialize($key, $cr, $fr, $ar, $r) .
                  hprose_fast_serialize($value, $cr, $fr, $ar, $r);
        }
        return $s . '}';
    }
    if (is_object($v)) {
        $h = hprose_hash($v, $ar);
        if (array_key_exists($h, $r)) {
            return 'r' . $r[$h] . ';';
        }
        if ($v instanceof stdClass) {
            $r[$h] = $r["length"]++;
            $v = (array)$v;
            $c = count($v);
            if ($c == 0) return 'a{}';
            $s = 'm' . $c . '{';
            foreach ($v as $key => &$value) {
                $s .= hprose_fast_serialize($key, $cr, $fr, $ar, $r) .
                      hprose_fast_serialize($value, $cr, $fr, $ar, $r);
            }
            return $s . '}';
        }
        if ($v instanceof DateTime) {
            $r[$h] = $r["length"]++;
            if ($v->getOffset() == 0) {
                return $v->format("\\DYmd\\THis.u\\Z");
            }
            return $v->format("\\DYmd\\THis.u;");
        }
        if (($v instanceof HproseDate) || ($v instanceof HproseDateTime)) {
            $r[$h] = $r["length"]++;
            if ($v->utc) {
                return 'D' . $v->toString(false);
            }
            return 'D' . $v->toString(false) . 'Z';
        }
        if ($v instanceof HproseTime) {
            $r[$h] = $r["length"]++;
            if ($v->utc) {
                return 'T' . $v->toString(false);
            }
            return 'T' . $v->toString(false) . 'Z';
        }
        if ($v instanceof HproseBytes) {
            $r[$h] = $r["length"]++;
            $c = strlen($v->value);
            if ($c == 0) return 'b""';
            return 'b' . $c . '"' . $v->value . '"';
        }
        if ($v instanceof HproseMap) {
            $r[$h] = $r["length"]++;
            $c = count($v->value);
            if ($c == 0) return 'a{}';
            $s = 'm' . $c . '{';
            foreach ($v->value as $key => &$value) {
                $s .= hprose_fast_serialize($key, $cr, $fr, $ar, $r) .
                      hprose_fast_serialize($value, $cr, $fr, $ar, $r);
            }
            return $s . '}';
        }
        $class = get_class($v);
        $alias = HproseClassManager::getClassAlias($class);
        $a = (array)$v;
        if (array_key_exists($alias, $cr)) {
            $index = $cr[$alias];
            $fields = $fr[$index];
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
                for ($i = 0; $i < $c; ++$i) {
                    $field = $fields[$i];
                    if ($field[0] === "\0") {
                        $field = substr($field, strpos($field, "\0", 1) + 1);
                    }
                    $fh = hprose_hash($v, $ar);
                    $r[$fh] = $r["length"]++;
                    $s .= 's' . ustrlen($field) . '"' . $field . '"';
                }
                $s .= '}';
            }
            else {
                $s .= '{}';
            }
            $index = count($fr);
            $cr[$alias] = $index;
            $fr[$index] = $fields;
        }
        $r[$h] = $r["length"]++;
        $s .= 'o' . $index . '{';
        for ($i = 0; $i < $c; ++$i) {
            $s .= hprose_fast_serialize($a[$fields[$i]], $cr, $fr, $ar, $r);
        }
        return $s . '}';
    }
    throw new HproseException('Not support to serialize this data');
}
?>