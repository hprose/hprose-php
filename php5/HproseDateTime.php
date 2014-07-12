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
 * HproseDateTime.php                                     *
 *                                                        *
 * hprose datetime class for php5.                        *
 *                                                        *
 * LastModified: Jul 12, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (!extension_loaded('hprose')) {

require_once('HproseDate.php');

class HproseDateTime extends HproseDate {
    public $hour;
    public $minute;
    public $second;
    public $microsecond = 0;
    public function __construct() {
        $args_num = func_num_args();
        $args = func_get_args();
        switch ($args_num) {
            case 0:
                $time = getdate();
                $timeofday = gettimeofday();
                $this->year = $time['year'];
                $this->month = $time['mon'];
                $this->day = $time['mday'];
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
                    $this->year = $time['year'];
                    $this->month = $time['mon'];
                    $this->day = $time['mday'];
                    $this->hour = $time['hours'];
                    $this->minute = $time['minutes'];
                    $this->second = $time['seconds'];
                    $this->utc = false;
                }
                elseif ($args[0] instanceof HproseDateTime) {
                    $this->year = $args[0]->year;
                    $this->month = $args[0]->month;
                    $this->day = $args[0]->day;
                    $this->hour = $args[0]->hour;
                    $this->minute = $args[0]->minute;
                    $this->second = $args[0]->second;
                    $this->microsecond = $args[0]->microsecond;
                    $this->utc = $args[0]->utc;
                }
                elseif ($args[0] instanceof HproseDate) {
                    $this->year = $args[0]->year;
                    $this->month = $args[0]->month;
                    $this->day = $args[0]->day;
                    $this->hour = 0;
                    $this->minute = 0;
                    $this->second = 0;
                    $this->utc = $args[0]->utc;
                }
                elseif ($args[0] instanceof HproseTime) {
                    $this->year = 1970;
                    $this->month = 1;
                    $this->day = 1;
                    $this->hour = $args[0]->hour;
                    $this->minute = $args[0]->minute;
                    $this->second = $args[0]->second;
                    $this->microsecond = $args[0]->microsecond;
                    $this->utc = $args[0]->utc;
                }
                else {
                    throw new Exception('Unexpected arguments');
                }
                break;
            case 2:
                if (($args[0] instanceof HproseDate) && ($args[1] instanceof HproseTime)) {
                    $this->year = $args[0]->year;
                    $this->month = $args[0]->month;
                    $this->day = $args[0]->day;
                    $this->hour = $args[1]->hour;
                    $this->minute = $args[1]->minute;
                    $this->second = $args[1]->second;
                    $this->microsecond = $args[1]->microsecond;
                    $this->utc = $args[0]->utc;
                }
                else {
                    throw new Exception('Unexpected arguments');
                }
                break;
            case 4:
                $this->utc = $args[3];
            case 3:
                if (!self::isValidDate($args[0], $args[1], $args[2])) {
                    throw new Exception('Unexpected arguments');
                }
                $this->year = $args[0];
                $this->month = $args[1];
                $this->day = $args[2];
                $this->hour = 0;
                $this->minute = 0;
                $this->second = 0;
                break;
            case 8:
                $this->utc = $args[7];
            case 7:
                if (($args[6] < 0) || ($args[6] > 999999)) {
                    throw new Exception('Unexpected arguments');
                }
                $this->microsecond = $args[6];
            case 6:
                if (!self::isValidDate($args[0], $args[1], $args[2])) {
                    throw new Exception('Unexpected arguments');
                }
                if (!self::isValidTime($args[3], $args[4], $args[5])) {
                    throw new Exception('Unexpected arguments');
                }
                $this->year = $args[0];
                $this->month = $args[1];
                $this->day = $args[2];
                $this->hour = $args[3];
                $this->minute = $args[4];
                $this->second = $args[5];
                break;
            default:
                throw new Exception('Unexpected arguments');
        }
    }

    public function addMicroseconds($microseconds) {
        if (!is_int($microseconds)) return false;
        if ($microseconds == 0) return true;
        $microsecond = $this->microsecond + $microseconds;
        $microseconds = $microsecond % 1000000;
        if ($microseconds < 0) {
            $microseconds += 1000000;
        }
        $seconds = (int)(($microsecond - $microseconds) / 1000000);
        if ($this->addSeconds($seconds)) {
            $this->microsecond = (int)$microseconds;
            return true;
        }
        else {
            return false;
        }
    }

    public function addSeconds($seconds) {
        if (!is_int($seconds)) return false;
        if ($seconds == 0) return true;
        $second = $this->second + $seconds;
        $seconds = $second % 60;
        if ($seconds < 0) {
            $seconds += 60;
        }
        $minutes = (int)(($second - $seconds) / 60);
        if ($this->addMinutes($minutes)) {
            $this->second = (int)$seconds;
            return true;
        }
        else {
            return false;
        }
    }
    public function addMinutes($minutes) {
        if (!is_int($minutes)) return false;
        if ($minutes == 0) return true;
        $minute = $this->minute + $minutes;
        $minutes = $minute % 60;
        if ($minutes < 0) {
            $minutes += 60;
        }
        $hours = (int)(($minute - $minutes) / 60);
        if ($this->addHours($hours)) {
            $this->minute = (int)$minutes;
            return true;
        }
        else {
            return false;
        }
    }
    public function addHours($hours) {
        if (!is_int($hours)) return false;
        if ($hours == 0) return true;
        $hour = $this->hour + $hours;
        $hours = $hour % 24;
        if ($hours < 0) {
            $hours += 24;
        }
        $days = (int)(($hour - $hours) / 24);
        if ($this->addDays($days)) {
            $this->hour = (int)$hours;
            return true;
        }
        else {
            return false;
        }
    }
    public function after($when) {
        if (!($when instanceof HproseDateTime)) {
            $when = new HproseDateTime($when);
        }
        if ($this->utc != $when->utc) return ($this->timestamp() > $when->timestamp());
        if ($this->year < $when->year) return false;
        if ($this->year > $when->year) return true;
        if ($this->month < $when->month) return false;
        if ($this->month > $when->month) return true;
        if ($this->day < $when->day) return false;
        if ($this->day > $when->day) return true;
        if ($this->hour < $when->hour) return false;
        if ($this->hour > $when->hour) return true;
        if ($this->minute < $when->minute) return false;
        if ($this->minute > $when->minute) return true;
        if ($this->second < $when->second) return false;
        if ($this->second > $when->second) return true;
        if ($this->microsecond < $when->microsecond) return false;
        if ($this->microsecond > $when->microsecond) return true;
        return false;
    }
    public function before($when) {
        if (!($when instanceof HproseDateTime)) {
            $when = new HproseDateTime($when);
        }
        if ($this->utc != $when->utc) return ($this->timestamp() < $when->timestamp());
        if ($this->year < $when->year) return true;
        if ($this->year > $when->year) return false;
        if ($this->month < $when->month) return true;
        if ($this->month > $when->month) return false;
        if ($this->day < $when->day) return true;
        if ($this->day > $when->day) return false;
        if ($this->hour < $when->hour) return true;
        if ($this->hour > $when->hour) return false;
        if ($this->minute < $when->minute) return true;
        if ($this->minute > $when->minute) return false;
        if ($this->second < $when->second) return true;
        if ($this->second > $when->second) return false;
        if ($this->microsecond < $when->microsecond) return true;
        if ($this->microsecond > $when->microsecond) return false;
        return false;
    }
    public function equals($when) {
        if (!($when instanceof HproseDateTime)) {
            $when = new HproseDateTime($when);
        }
        if ($this->utc != $when->utc) return ($this->timestamp() == $when->timestamp());
        return (($this->year == $when->year) &&
            ($this->month == $when->month) &&
            ($this->day == $when->day) &&
            ($this->hour == $when->hour) &&
            ($this->minute == $when->minute) &&
            ($this->second == $when->second) &&
            ($this->microsecond == $when->microsecond));
    }
    public function timestamp() {
        if ($this->utc) {
            return gmmktime($this->hour,
                            $this->minute,
                            $this->second,
                            $this->month,
                            $this->day,
                            $this->year) +
                   ($this->microsecond / 1000000);
        }
        else {
            return mktime($this->hour,
                          $this->minute,
                          $this->second,
                          $this->month,
                          $this->day,
                          $this->year) +
                   ($this->microsecond / 1000000);
        }
    }
    public function toString($fullformat = true) {
        if ($this->microsecond == 0) {
            $format = ($fullformat ? '%04d-%02d-%02dT%02d:%02d:%02d'
                                   : '%04d%02d%02dT%02d%02d%02d');
            $str = sprintf($format,
                           $this->year, $this->month, $this->day,
                           $this->hour, $this->minute, $this->second);
        }
        if ($this->microsecond % 1000 == 0) {
            $format = ($fullformat ? '%04d-%02d-%02dT%02d:%02d:%02d.%03d'
                                   : '%04d%02d%02dT%02d%02d%02d.%03d');
            $str = sprintf($format,
                           $this->year, $this->month, $this->day,
                           $this->hour, $this->minute, $this->second,
                           (int)($this->microsecond / 1000));
        }
        else {
            $format = ($fullformat ? '%04d-%02d-%02dT%02d:%02d:%02d.%06d'
                                   : '%04d%02d%02dT%02d%02d%02d.%06d');
            $str = sprintf($format,
                           $this->year, $this->month, $this->day,
                           $this->hour, $this->minute, $this->second,
                           $this->microsecond);
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
        return HproseTime::isValidTime($hour, $minute, $second, $microsecond);
    }
}

} // endif (!extension_loaded('hprose'))
?>