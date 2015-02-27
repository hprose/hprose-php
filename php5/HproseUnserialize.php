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
 * HproseUnserialize.php                                  *
 *                                                        *
 * hprose unserialize library for php5.                   *
 *                                                        *
 * LastModified: Feb 27, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (!extension_loaded('hprose')) {

require_once('HproseCommon.php');
require_once('HproseClassManager.php');

// public functions
function &hprose_unserialize_with_stream($s, $simple = false) {
    $o = new stdClass();
    $s->mark();
    $o->s = $s->readfull();
    $o->p = 0;
    $o->cr = array();
    if ($simple) {
        $v = hprose_simple_unserialize($o);
    }
    else {
        $o->r = array();
        $v = hprose_fast_unserialize($o);
    }
    $s->reset();
    $s->skip($o->p);
    return $v;
}

function hprose_unserialize_string_with_stream($s, $simple = false) {
    $o = new stdClass();
    $s->mark();
    $o->s = $s->readfull();
    $o->p = 0;
    if ($simple) {
        $v = hprose_simple_unserialize_string($o);
    }
    else {
        $o->r = array();
        $v = hprose_fast_unserialize_string($o);
    }
    $s->reset();
    $s->skip($o->p);
    return $v;
}

function &hprose_unserialize_list_with_stream($s) {
    $o = new stdClass();
    $s->mark();
    $o->s = $s->readfull();
    $o->p = 0;
    $o->cr = array();
    $o->r = array();
    $v = hprose_fast_read_list($o);
    $s->reset();
    $s->skip($o->p);
    return $v;
}

function &hprose_unserialize($s, $simple = false) {
    $o = new stdClass();
    $o->s = $s;
    $o->p = 0;
    $o->cr = array();
    if ($simple) {
        $v = hprose_simple_unserialize($o);
    }
    else {
        $o->r = array();
        $v = hprose_fast_unserialize($o);
    }
    return $v;
}

// private functions

/* $t is a 1 byte character. */
function hprose_readuntil($o, $t) {
    $p = strpos($o->s, $t, $o->p);
    if ($p != false) {
        $r = substr($o->s, $o->p, $p - $o->p);
        $o->p = $p + 1;
    }
    else {
        $r = substr($o->s, $o->p);
        $o->p = strlen($o->s);
    }
    return $r;
}

function &hprose_read_ref($o) {
    $i = (int)hprose_readuntil($o, ';');
    if (is_array($o->r[$i])) {
        return $o->r[$i];
    }
    $r = $o->r[$i];
    return $r;
}

function hprose_read_utf8char($o) {
    $c = $o->s[$o->p++];
    switch (ord($c) >> 4) {
        case 0:
        case 1:
        case 2:
        case 3:
        case 4:
        case 5:
        case 6:
        case 7: return $c;
        case 12:
        case 13: return $c . $o->s[$o->p++];
        case 14: return $c . $o->s[$o->p++] . $o->s[$o->p++];
    }
    throw new Exception('bad utf-8 encoding');
}

function hprose_read_string($o) {
    $l = (int)hprose_readuntil($o, '"');
    $p = $o->p;
    for ($i = 0; $i < $l; ++$i) {
        switch (ord($o->s[$o->p]) >> 4) {
            case 0:
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
            case 7: ++$o->p; break;
            case 12:
            case 13: $o->p += 2; break;
            case 14: $o->p += 3; break;
            case 15: $o->p += 4; ++$i; break;
            default: throw new Exception('bad utf-8 encoding');
        }
    }
    $s = substr($o->s, $p, $o->p - $p);
    ++$o->p;
    return $s;
}

function hprose_simple_unserialize_string($o) {
    switch ($o->s[$o->p++]) {
        case 'u': return hprose_read_utf8char($o);
        case 's': return hprose_read_string($o);
        case 'E': throw new Exception(hprose_simple_unserialize_string($o));
    }
    throw new Exception("Can't unserialize '$o->s' as string.");
}

function hprose_fast_unserialize_string($o) {
    switch ($o->s[$o->p++]) {
        case 'u': return hprose_read_utf8char($o);
        case 's': return $o->r[] = hprose_read_string($o);
        case 'r': return hprose_read_ref($o);
        case 'E': throw new Exception(hprose_fast_unserialize_string($o));
    }
    throw new Exception("Can't unserialize '$o->s' as string.");
}

function hprose_read_bytes($o) {
    $c = (int)hprose_readuntil($o, '"');
    $bytes = substr($o->s, $o->p, $c);
    $o->p += $c + 1;
    return $bytes;
}

function hprose_read_guid($o) {
    $g = substr($o->s, $o->p + 1, 36);
    $o->p += 38;
    return $g;
}

function __hprose_read_time($o) {
    $hour = (int)substr($o->s, $o->p, 2);
    $o->p += 2;
    $min = (int)substr($o->s, $o->p, 2);
    $o->p += 2;
    $sec = (int)substr($o->s, $o->p, 2);
    $o->p += 2;
    $msec = 0;
    $tag = $o->s[$o->p++];
    if ($tag == '.') {
        $msec = (int)substr($o->s, $o->p, 3) * 1000;
        $o->p += 3;
        $tag = $o->s[$o->p++];
        if (($tag >= '0') && ($tag < '9')) {
            $msec += (int)$tag * 100 + (int)substr($o->s, $o->p, 2);
            $o->p += 2;
            $tag = $o->s[$o->p++];
            if (($tag >= '0') && ($tag < '9')) {
                $o->p += 2;
                $tag = $o->s[$o->p++];
            }
        }
    }
    return array($hour, $min, $sec, $msec, $tag);
}

function hprose_read_date($o) {
    $year = (int)substr($o->s, $o->p, 4);
    $o->p += 4;
    $mon = (int)substr($o->s, $o->p, 2);
    $o->p += 2;
    $day = (int)substr($o->s, $o->p, 2);
    $o->p += 2;
    $tag = $o->s[$o->p++];
    if ($tag == 'T') {
        list($hour, $min, $sec, $msec, $tag) = __hprose_read_time($o);
        $date = new HproseDateTime($year, $mon, $day, $hour, $min, $sec, $msec, $tag == 'Z');
    }
    else {
        $date = new HproseDate($year, $mon, $day, $tag == 'Z');
    }
    return $date;
}

function hprose_read_time($o) {
    list($hour, $min, $sec, $msec, $tag) = __hprose_read_time($o);
    $date = new HproseTime($hour, $min, $sec, $msec, $tag == 'Z');
    return $date;
}

function &hprose_simple_read_list($o) {
    $a = array();
    $c = (int)hprose_readuntil($o, '{');
    for ($i = 0; $i < $c; ++$i) {
        $a[] = &hprose_simple_unserialize($o);
    }
    ++$o->p;
    return $a;
}

function &hprose_fast_read_list($o) {
    $a = array();
    $o->r[] = &$a;
    $c = (int)hprose_readuntil($o, '{');
    for ($i = 0; $i < $c; ++$i) {
        $a[] = &hprose_fast_unserialize($o);
    }
    ++$o->p;
    return $a;
}

function &hprose_simple_read_map($o) {
    $m = array();
    $c = (int)hprose_readuntil($o, '{');
    for ($i = 0; $i < $c; ++$i) {
        $k = hprose_simple_unserialize($o);
        $m[$k] = &hprose_simple_unserialize($o);
    }
    ++$o->p;
    return $m;
}

function &hprose_fast_read_map($o) {
    $m = array();
    $o->r[] = &$m;
    $c = (int)hprose_readuntil($o, '{');
    for ($i = 0; $i < $c; ++$i) {
        $k = hprose_fast_unserialize($o);
        $m[$k] = &hprose_fast_unserialize($o);
    }
    ++$o->p;
    return $m;
}

function hprose_simple_read_class($o) {
    $classname = HproseClassManager::getClass(hprose_read_string($o));
    $c = (int)hprose_readuntil($o, '{');
    $fields = array();
    for ($i = 0; $i < $c; ++$i) {
        $fields[] = hprose_simple_unserialize_string($o);
    }
    ++$o->p;
    $o->cr[] = array($classname, $fields);
}

function hprose_fast_read_class($o) {
    $classname = HproseClassManager::getClass(hprose_read_string($o));
    $c = (int)hprose_readuntil($o, '{');
    $fields = array();
    for ($i = 0; $i < $c; ++$i) {
        $fields[] = hprose_fast_unserialize_string($o);
    }
    ++$o->p;
    $o->cr[] = array($classname, $fields);
}

function hprose_simple_read_object($o) {
    list($classname, $fields) = $o->cr[(int)hprose_readuntil($o, '{')];
    $obj = new $classname;
    $c = count($fields);
    if (class_exists('ReflectionClass')) {
        $reflector = new ReflectionClass($classname);
        for ($i = 0; $i < $c; ++$i) {
            $field = $fields[$i];
            if ($reflector->hasProperty($field)) {
                $property = $reflector->getProperty($field);
                $property->setAccessible(true);
                $property->setValue($obj, hprose_simple_unserialize($o));
            }
            else {
                $obj->$field = &hprose_simple_unserialize($o);
            }
        }
    }
    else {
        for ($i = 0; $i < $c; ++$i) {
            $obj->$fields[$i] = &hprose_simple_unserialize($o);
        }
    }
    ++$o->p;
    return $obj;
}

function hprose_fast_read_object($o) {
    list($classname, $fields) = $o->cr[(int)hprose_readuntil($o, '{')];
    $o->r[] = $obj = new $classname;
    $c = count($fields);
    if (class_exists('ReflectionClass')) {
        $reflector = new ReflectionClass($classname);
        for ($i = 0; $i < $c; ++$i) {
            $field = $fields[$i];
            if ($reflector->hasProperty($field)) {
                $property = $reflector->getProperty($field);
                $property->setAccessible(true);
                $property->setValue($obj, hprose_fast_unserialize($o));
            }
            else {
                $obj->$field = &hprose_fast_unserialize($o);
            }
        }
    }
    else {
        for ($i = 0; $i < $c; ++$i) {
            $obj->$fields[$i] = &hprose_fast_unserialize($o);
        }
    }
    ++$o->p;
    return $obj;
}

function &hprose_simple_unserialize($o) {
    switch ($o->s[$o->p++]) {
        case '0': $result = 0; break;
        case '1': $result = 1; break;
        case '2': $result = 2; break;
        case '3': $result = 3; break;
        case '4': $result = 4; break;
        case '5': $result = 5; break;
        case '6': $result = 6; break;
        case '7': $result = 7; break;
        case '8': $result = 8; break;
        case '9': $result = 9; break;
        case 'n': $result = null; break;
        case 'e': $result = ''; break;
        case 't': $result = true; break;
        case 'f': $result = false; break;
        case 'N': $result = log(-1); break;
        case 'I': $result = $o->s[$o->p++] == '-' ? log(0) : -log(0); break;
        case 'i': $result = (int)hprose_readuntil($o, ';'); break;
        case 'l': $result = hprose_readuntil($o, ';'); break;
        case 'd': $result = (double)hprose_readuntil($o, ';'); break;
        case 'u': $result = hprose_read_utf8char($o); break;
        case 's': $result = hprose_read_string($o); break;
        case 'b': $result = hprose_read_bytes($o); break;
        case 'g': $result = hprose_read_guid($o); break;
        case 'D': $result = hprose_read_date($o); break;
        case 'T': $result = hprose_read_time($o); break;
        case 'a': $result = &hprose_simple_read_list($o); break;
        case 'm': $result = &hprose_simple_read_map($o); break;
        case 'c': hprose_simple_read_class($o);
                  $result = hprose_simple_unserialize($o); break;
        case 'o': $result = hprose_simple_read_object($o); break;
        case 'E': throw new Exception(hprose_simple_unserialize_string($o));
        default: throw new Exception("Can't unserialize '{$o->s}' in simple mode.");
    }
    return $result;
}

function &hprose_fast_unserialize($o) {
    switch ($o->s[$o->p++]) {
        case '0': $result = 0; break;
        case '1': $result = 1; break;
        case '2': $result = 2; break;
        case '3': $result = 3; break;
        case '4': $result = 4; break;
        case '5': $result = 5; break;
        case '6': $result = 6; break;
        case '7': $result = 7; break;
        case '8': $result = 8; break;
        case '9': $result = 9; break;
        case 'n': $result = null; break;
        case 'e': $result = ''; break;
        case 't': $result = true; break;
        case 'f': $result = false; break;
        case 'N': $result = log(-1); break;
        case 'I': $result = $o->s[$o->p++] == '-' ? log(0) : -log(0); break;
        case 'i': $result = (int)hprose_readuntil($o, ';'); break;
        case 'l': $result = hprose_readuntil($o, ';'); break;
        case 'd': $result = (double)hprose_readuntil($o, ';'); break;
        case 'u': $result = hprose_read_utf8char($o); break;
        case 's': $o->r[] = $result = hprose_read_string($o); break;
        case 'b': $o->r[] = $result = hprose_read_bytes($o); break;
        case 'g': $o->r[] = $result = hprose_read_guid($o); break;
        case 'D': $o->r[] = $result = hprose_read_date($o); break;
        case 'T': $o->r[] = $result = hprose_read_time($o); break;
        case 'a': $result = &hprose_fast_read_list($o); break;
        case 'm': $result = &hprose_fast_read_map($o); break;
        case 'c': hprose_fast_read_class($o);
                  $result = hprose_fast_unserialize($o); break;
        case 'o': $result = hprose_fast_read_object($o); break;
        case 'r': $result = &hprose_read_ref($o); break;
        case 'E': throw new Exception(hprose_fast_unserialize_string($o));
        default: throw new Exception("Can't unserialize '{$o->s}'.");
    }
    return $result;
}

} // endif (!extension_loaded('hprose'))
