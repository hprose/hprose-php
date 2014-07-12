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
 * HproseCommon.php                                       *
 *                                                        *
 * hprose common library for php5.                        *
 *                                                        *
 * LastModified: Jul 12, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (!extension_loaded('hprose')) {

require_once('HproseResultMode.php');
require_once('HproseFilter.php');
require_once('HproseDate.php');
require_once('HproseTime.php');
require_once('HproseDateTime.php');

class HproseBytes {
    public $value;
    public function __construct($val) {
        $this->value = $val;
    }
    public function __toString() {
        return (string)$this->value;
    }
}

function &bytes($val) {
    $b = new HproseBytes($val);
    return $b;
}

class HproseMap {
    public $value;
    public function __construct(array &$val) {
        $this->value = &$val;
    }
    public function __toString() {
        return "Map";
    }
}

function &map($val) {
    $m = new HproseMap($val);
    return $m;
}

/*
 integer is_utf8(string $s)
 if $s is UTF-8 String, return 1 else 0
 */
if (function_exists('mb_detect_encoding')) {
    function is_utf8($s) {
        return mb_detect_encoding($s, 'UTF-8', true) === 'UTF-8';
    }
}
elseif (function_exists('iconv')) {
    function is_utf8($s) {
        return iconv('UTF-8', 'UTF-8//IGNORE', $s) === $s;
    }
}
else {
    function is_utf8($s) {
        $len = strlen($s);
        for($i = 0; $i < $len; ++$i){
            $c = ord($s{$i});
            switch ($c >> 4) {
                case 0:
                case 1:
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                case 7:
                    break;
                case 12:
                case 13:
                    if ((ord($s{++$i}) >> 6) != 0x2) return false;
                    break;
                case 14:
                    if ((ord($s{++$i}) >> 6) != 0x2) return false;
                    if ((ord($s{++$i}) >> 6) != 0x2) return false;
                    break;
                case 15:
                    $b = $s{++$i};
                    if ((ord($b) >> 6) != 0x2) return false;
                    if ((ord($s{++$i}) >> 6) != 0x2) return false;
                    if ((ord($s{++$i}) >> 6) != 0x2) return false;
                    if (((($c & 0xf) << 2) | (($b >> 4) & 0x3)) > 0x10) return false;
                    break;
                default:
                    return false;
            }
        }
        return true;
    }
}

/*
 integer ustrlen(string $s)
 $s must be a UTF-8 String, return the Unicode code unit (not code point) length
 */
if (function_exists('iconv')) {
    function ustrlen($s) {
        return strlen(iconv('UTF-8', 'UTF-16LE', $s)) >> 1;
    }
}
elseif (function_exists('mb_convert_encoding')) {
    function ustrlen($s) {
        return strlen(mb_convert_encoding($s, "UTF-16LE", "UTF-8")) >> 1;
    }
}
else {
    function ustrlen($s) {
        $pos = 0;
        $length = strlen($s);
        $len = $length;
        while ($pos < $length) {
            $a = ord($s{$pos++});
            if ($a < 0x80) {
                continue;
            }
            elseif (($a & 0xE0) == 0xC0) {
                ++$pos;
                --$len;
            }
            elseif (($a & 0xF0) == 0xE0) {
                $pos += 2;
                $len -= 2;
            }
            elseif (($a & 0xF8) == 0xF0) {
                $pos += 3;
                $len -= 2;
            }
        }
        return $len;
    }
}

/*
 bool is_list(array $a)
 if $a is list, return true else false
 */
function is_list(array $a) {
    $count = count($a);
    if ($count === 0) return true;
    return !array_diff_key($a, array_fill(0, $count, NULL));
}

/*
 mixed array_ref_search(mixed &$value, array $array)
 if $value ref in $array, return the index else false
*/
function array_ref_search(&$value, $array) {
    if (!is_array($value)) return array_search($value, $array, true);
    $temp = $value;
    foreach ($array as $i => &$ref) {
        if (($ref === ($value = 1)) && ($ref === ($value = 0))) {
            $value = $temp;
            return $i;
        }
    }
    $value = $temp;
    return false;
}

/*
 string spl_object_hash(object $obj)
 This function returns a unique identifier for the object.
 This id can be used as a hash key for storing objects or for identifying an object.
*/
if (!function_exists('spl_object_hash')) {
    function spl_object_hash_callback() {
        return "";
    }

    function spl_object_hash($object) {
        ob_start("spl_object_hash_callback");
        var_dump($object);
        preg_match('[#(\d+)]', ob_get_clean(), $match);
        return $match[1];
    }
}

} // endif (!extension_loaded('hprose'))
?>