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
 * HproseTime.php                                         *
 *                                                        *
 * hprose time class for php5.                            *
 *                                                        *
 * LastModified: Jul 12, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (!extension_loaded('hprose')) {

class HproseTime {
    public $hour;
    public $minute;
    public $second;
    public $microsecond = 0;
    public $utc = false;
    public function __construct() {
        $args_num = func_num_args();
        $args = func_get_args();
        switch ($args_num) {
            case 0:
                $time = getdate();
                $timeofday = gettimeofday();
                $this->hour = $time['hours'];
                $this->minute = $time['minutes'];
                $this->second = $time['seconds'];
                $this->microsecond = $timeofday['usec'];
                break;
            case 1:
                $time = false;
                if (is_int($args[0])) {
                    $time = getdate($args[0]);
                }
                elseif (is_string($args[0])) {
                    $time = getdate(strtotime($args[0]));
                }
                if (is_array($time)) {
                    $this->hour = $time['hours'];
                    $this->minute = $time['minutes'];
                    $this->second = $time['seconds'];
                }
                elseif ($args[0] instanceof HproseTime) {
                    $this->hour = $args[0]->hour;
                    $this->minute = $args[0]->minute;
                    $this->second = $args[0]->second;
                    $this->microsecond = $args[0]->microsecond;
                }
                else {
                    throw new Exception('Unexpected arguments');
                }
                break;
            case 5:
                $this->utc = $args[4];
            case 4:
                if (($args[3] < 0) || ($args[3] > 999999)) {
                    throw new Exception('Unexpected arguments');
                }
                $this->microsecond = $args[3];
            case 3:
                if (!self::isValidTime($args[0], $args[1], $args[2])) {
                    throw new Exception('Unexpected arguments');
                }
                $this->hour = $args[0];
                $this->minute = $args[1];
                $this->second = $args[2];
                break;
            default:
                throw new Exception('Unexpected arguments');
        }
    }
    public function timestamp() {
        if ($this->utc) {
            return gmmktime($this->hour, $this->minute, $this->second) +
                   ($this->microsecond / 1000000);
        }
        else {
            return mktime($this->hour, $this->minute, $this->second) +
                   ($this->microsecond / 1000000);
        }
    }
    public function toString($fullformat = true) {
        if ($this->microsecond == 0) {
            $format = ($fullformat ? '%02d:%02d:%02d': '%02d%02d%02d');
            $str = sprintf($format, $this->hour, $this->minute, $this->second);
        }
        else if ($this->microsecond % 1000 == 0) {
            $format = ($fullformat ? '%02d:%02d:%02d.%03d': '%02d%02d%02d.%03d');
            $str = sprintf($format, $this->hour, $this->minute, $this->second, (int)($this->microsecond / 1000));
        }
        else {
            $format = ($fullformat ? '%02d:%02d:%02d.%06d': '%02d%02d%02d.%06d');
            $str = sprintf($format, $this->hour, $this->minute, $this->second, $this->microsecond);
        }
        if ($this->utc) {
            $str .= 'Z';
        }
        return $str;
    }
    public function __toString() {
        return $this->toString();
    }
    public static function isValidTime($hour, $minute, $second, $microsecond = 0) {
        return !(($hour < 0) || ($hour > 23) ||
            ($minute < 0) || ($minute > 59) ||
            ($second < 0) || ($second > 60) ||
            ($microsecond < 0) || ($microsecond > 999999));
    }
}

} // endif (!extension_loaded('hprose'))
?>