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
 * LastModified: Jun 21, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

require_once('HproseCommon.php');
require_once('HproseClassManager.php');

function &hprose_unserialize(&$s, $simple = false) {
    $cr = array();
    $p = 0;
    if ($simple) {
        $v = &hprose_simple_unserialize($s, $p, $cr);
    }
    else {
        $r = array();
        $v = &hprose_fast_unserialize($s, $p, $cr, $r);
    }
    $s = (string)substr($s, $p);
    return $v;
}

/* $t is a 1 byte character. */
function hprose_readuntil(&$s, &$p, $t) {
    $pos = strpos($s, $t, $p);
    if ($pos != false) {
        $r = substr($s, $p, $pos - $p);
        $p = $pos + 1;
    }
    else {
        $r = substr($s, $p);
        $p = strlen($s);
    }
    return $r;
}

function &hprose_read_ref(&$s, &$p, &$r) {
    $ref = &$r[(int)hprose_readuntil($s, $p, ';')];
    if (is_array($ref)) {
        return $ref;
    }
    $copy = $ref;
    return $copy;
}

function hprose_simple_read_utf8char(&$s, &$p) {
    $c = $s[$p++];
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
        case 13: return $c . $s[$p++];
        case 14: return $c . $s[$p++] . $s[$p++];
    }
    throw new HproseException('bad utf-8 encoding');
}

function hprose_simple_read_string(&$s, &$p) {
    $l = (int)hprose_readuntil($s, $p, '"');
    $o = $p;
    for ($i = 0; $i < $l; ++$i) {
        switch (ord($s[$p]) >> 4) {
            case 0:
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
            case 7: ++$p; break;
            case 12:
            case 13: $p += 2; break;
            case 14: $p += 3; break;
            case 15: $p += 4; ++$i; break;
            default: throw new HproseException('bad utf-8 encoding');
        }
    }
    $str = substr($s, $o, $p - $o);
    ++$p;
    return $str;
}

function hprose_simple_unserialize_string(&$s, &$p) {
    switch ($s[$p++]) {
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
        case 'n': return NULL;
        case 'e': return '';
        case 't': return 'true';
        case 'f': return 'false';
        case 'N': return 'NaN';
        case 'I': return $s[$p++] == '-' ? '-Infinite' : 'Infinite';
        case 'i': return readuntil($s, $p, ';');
        case 'l': return readuntil($s, $p, ';');
        case 'd': return readuntil($s, $p, ';');
        case 'u': return hprose_simple_read_utf8char($s, $p);
        case 's': return hprose_simple_read_string($s, $p);
        case 'b': return hprose_simple_read_bytes($s, $p);
        case 'g': return hprose_simple_read_guid($s, $p);
        case 'E': throw new HproseException(hprose_simple_read_string($s, $p));
    }
    throw new HproseException("Can't unserialize '$s' as string.");
}

function hprose_fast_unserialize_string(&$s, &$p, &$r) {
    switch ($s[$p++]) {
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
        case 'n': return NULL;
        case 'e': return '';
        case 't': return 'true';
        case 'f': return 'false';
        case 'N': return 'NaN';
        case 'I': return $s[$p++] == '-' ? '-Infinite' : 'Infinite';
        case 'i': return readuntil($s, $p, ';');
        case 'l': return readuntil($s, $p, ';');
        case 'd': return readuntil($s, $p, ';');
        case 'u': return hprose_simple_read_utf8char($s, $p);
        case 's': return $r[] = hprose_simple_read_string($s, $p);
        case 'b': return $r[] = hprose_simple_read_bytes($s, $p);
        case 'g': return $r[] = hprose_simple_read_guid($s, $p);
        case 'r': return hprose_read_ref($s, $p, $r);
        case 'E': throw new HproseException(hprose_simple_read_string($s, $p));
    }
    throw new HproseException("Can't unserialize '$s' as string.");
}

function hprose_simple_read_bytes(&$s, &$p) {
    $c = (int)hprose_readuntil($s, $p, '"');
    $bytes = substr($s, $p, $c);
    $p += $c + 1;
    return $bytes;
}

function hprose_simple_read_guid(&$s, &$p) {
    $g = substr($s, $p + 1, 36);
    $p += 38;
    return $g;
}

function hprose_read_time(&$s, &$p) {
    $hour = (int)substr($s, $p, 2);
    $p += 2;
    $min = (int)substr($s, $p, 2);
    $p += 2;
    $sec = (int)substr($s, $p, 2);
    $p += 2;
    $msec = 0;
    $tag = $s[$p++];
    if ($tag == '.') {
        $msec = (int)substr($s, $p, 3) * 1000;
        $p += 3;
        $tag = $s[$p++];
        if (($tag >= '0') && ($tag < '9')) {
            $msec += (int)$tag * 100 + (int)substr($s, $p, 2);
            $p += 2;
            $tag = $s[$p++];
            if (($tag >= '0') && ($tag < '9')) {
                $p += 2;
                $tag = $s[$p++];
            }
        }
    }
    return array($hour, $min, $sec, $msec);
}

function hprose_simple_read_date(&$s, &$p) {
    $year = (int)substr($s, $p, 4);
    $p += 4;
    $mon = (int)substr($s, $p, 2);
    $p += 2;
    $day = (int)substr($s, $p, 2);
    $p += 2;
    $tag = $s[$p++];
    if ($tag == 'T') {
        list($hour, $min, $sec, $msec) = hprose_read_time($s, $p);
        $date = new HproseDateTime($year, $mon, $day, $hour, $min, $sec, $msec, $tag == 'Z');
    }
    else {
        $date = new HproseDate($year, $month, $day, $tag == 'Z');
    }
    return $date;
}

function hprose_simple_read_time(&$s, &$p) {
    list($hour, $min, $sec, $msec) = hprose_read_time($s, $p);
    $date = new HproseTime($hour, $min, $sec, $msec, $tag == 'Z');
    return $date;
}

function &hprose_simple_read_list(&$s, &$p, &$cr) {
    $a = array();
    $c = (int)hprose_readuntil($s, $p, '{');
    for ($i = 0; $i < $c; ++$i) {
        $a[] = &hprose_simple_unserialize($s, $p, $cr);
    }
    ++$p;
    return $a;
}

function &hprose_fast_read_list(&$s, &$p, &$cr, &$r) {
    $a = array();
    $r[] = &$a;
    $c = (int)hprose_readuntil($s, $p, '{');
    for ($i = 0; $i < $c; ++$i) {
        $a[] = &hprose_fast_unserialize($s, $p, $cr, $r);
    }
    ++$p;
    return $a;
}

function &hprose_simple_read_map(&$s, &$p, &$cr) {
    $m = array();
    $c = (int)hprose_readuntil($s, $p, '{');
    for ($i = 0; $i < $c; ++$i) {
        $k = &hprose_simple_unserialize($s, $p, $cr);
        $m[$k] = &hprose_simple_unserialize($s, $p, $cr);
    }
    ++$p;
    return $m;
}

function &hprose_fast_read_map(&$s, &$p, &$cr, &$r) {
    $m = array();
    $r[] = &$m;
    $c = (int)hprose_readuntil($s, $p, '{');
    for ($i = 0; $i < $c; ++$i) {
        $k = &hprose_fast_unserialize($s, $p, $cr, $r);
        $m[$k] = &hprose_fast_unserialize($s, $p, $cr, $r);
    }
    ++$p;
    return $m;
}

function hprose_simple_read_class(&$s, &$p, &$cr) {
    $classname = HproseClassManager::getClass(hprose_simple_read_string($s, $p));
    $c = (int)hprose_readuntil($s, $p, '{');
    $fields = array();
    for ($i = 0; $i < $c; ++$i) {
        $fields[] = hprose_simple_unserialize_string($s, $p);
    }
    ++$p;
    $cr[] = array($classname, $fields);
}

function hprose_fast_read_class(&$s, &$p, &$cr, &$r) {
    $classname = HproseClassManager::getClass(hprose_simple_read_string($s, $p));
    $c = (int)hprose_readuntil($s, $p, '{');
    $fields = array();
    for ($i = 0; $i < $c; ++$i) {
        $fields[] = hprose_fast_unserialize_string($s, $p, $r);
    }
    ++$p;
    $cr[] = array($classname, $fields);
}

function hprose_simple_read_object(&$s, &$p, &$cr) {
    list($classname, $fields) = $cr[(int)hprose_readuntil($s, $p, '{')];
    $o = new $classname;
    $c = count($fields);
    if (class_exists('ReflectionClass')) {
        $reflector = new ReflectionClass($classname);
        for ($i = 0; $i < $c; ++$i) {
            $field = $fields[$i];
            if ($reflector->hasProperty($field)) {
                $property = $reflector->getProperty($field);
                $property->setAccessible(true);
                $property->setValue($o, hprose_simple_unserialize($s, $p, $cr));
            }
            else {
                $o->$field = &hprose_simple_unserialize($s, $p, $cr);
            }
        }
    }
    else {
        for ($i = 0; $i < $c; ++$i) {
            $o->$fields[$i] = &hprose_simple_unserialize($s, $p, $cr);
        }
    }
    ++$p;
    return $o;
}

function hprose_fast_read_object(&$s, &$p, &$cr, &$r) {
    list($classname, $fields) = $cr[(int)hprose_readuntil($s, $p, '{')];
    $r[] = $o = new $classname;
    $c = count($fields);
    if (class_exists('ReflectionClass')) {
        $reflector = new ReflectionClass($classname);
        for ($i = 0; $i < $c; ++$i) {
            $field = $fields[$i];
            if ($reflector->hasProperty($field)) {
                $property = $reflector->getProperty($field);
                $property->setAccessible(true);
                $property->setValue($o, hprose_fast_unserialize($s, $p, $cr, $r));
            }
            else {
                $o->$field = &hprose_fast_unserialize($s, $p, $cr, $r);
            }
        }
    }
    else {
        for ($i = 0; $i < $c; ++$i) {
            $o->$fields[$i] = &hprose_fast_unserialize($s, $p, $cr, $r);
        }
    }
    ++$p;
    return $o;
}

function &hprose_simple_unserialize(&$s, &$p, &$cr) {
    switch ($s[$p++]) {
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
        case 'n': $result = NULL; break;
        case 'e': $result = ''; break;
        case 't': $result = true; break;
        case 'f': $result = false; break;
        case 'N': $result = log(-1); break;
        case 'I': $result = $s[$p++] == '-' ? log(0) : -log(0); break;
        case 'i': $result = (int)hprose_readuntil($s, $p, ';'); break;
        case 'l': $result = hprose_readuntil($s, $p, ';'); break;
        case 'd': $result = (double)hprose_readuntil($s, $p, ';'); break;
        case 'u': $result = hprose_simple_read_utf8char($s, $p); break;
        case 's': $result = hprose_simple_read_string($s, $p); break;
        case 'b': $result = hprose_simple_read_bytes($s, $p); break;
        case 'g': $result = hprose_simple_read_guid($s, $p); break;
        case 'D': $result = hprose_simple_read_date($s, $p); break;
        case 'T': $result = hprose_simple_read_time($s, $p); break;
        case 'a': $result = &hprose_simple_read_list($s, $p, $cr); break;
        case 'm': $result = &hprose_simple_read_map($s, $p, $cr); break;
        case 'c': hprose_simple_read_class($s, $p, $cr);
                  $result = hprose_simple_unserialize($s, $p, $cr); break;
        case 'o': $result = hprose_simple_read_object($s, $p, $cr); break;
        case 'E': throw new HproseException(hprose_simple_read_string($s, $p));
        default: throw new HproseException("Can't unserialize '$s' in simple mode.");
    }
    return $result;
}

function &hprose_fast_unserialize(&$s, &$p, &$cr, &$r) {
    switch ($s[$p++]) {
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
        case 'n': $result = NULL; break;
        case 'e': $result = ''; break;
        case 't': $result = true; break;
        case 'f': $result = false; break;
        case 'N': $result = log(-1); break;
        case 'I': $result = $s[$p++] == '-' ? log(0) : -log(0); break;
        case 'i': $result = (int)hprose_readuntil($s, $p, ';'); break;
        case 'l': $result = hprose_readuntil($s, $p, ';'); break;
        case 'd': $result = (double)hprose_readuntil($s, $p, ';'); break;
        case 'u': $result = hprose_simple_read_utf8char($s, $p); break;
        case 's': $r[] = $result = hprose_simple_read_string($s, $p); break;
        case 'b': $r[] = $result = hprose_simple_read_bytes($s, $p); break;
        case 'g': $r[] = $result = hprose_simple_read_guid($s, $p); break;
        case 'D': $r[] = $result = hprose_simple_read_date($s, $p); break;
        case 'T': $r[] = $result = hprose_simple_read_time($s, $p); break;
        case 'a': $result = &hprose_fast_read_list($s, $p, $cr, $r); break;
        case 'm': $result = &hprose_fast_read_map($s, $p, $cr, $r); break;
        case 'c': hprose_fast_read_class($s, $p, $cr, $r);
                  $result = hprose_fast_unserialize($s, $p, $cr, $r); break;
        case 'o': $result = hprose_fast_read_object($s, $p, $cr, $r); break;
        case 'r': $result = &hprose_read_ref($s, $p, $r); break;
        case 'E': throw new HproseException(hprose_simple_read_string($s, $p));
        default: throw new HproseException("Can't unserialize '$s'.");
    }
    return $result;
}

?>