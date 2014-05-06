<?php
/**********************************************************\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: http://www.hprose.com/                 |
|                   http://www.hprose.net/                 |
|                   http://www.hprose.org/                 |
|                                                          |
\**********************************************************/

/**********************************************************\
 *                                                        *
 * HproseDate.php                                         *
 *                                                        *
 * hprose date class for php5.                            *
 *                                                        *
 * LastModified: Jan 2, 2014                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

class HproseDate {
    public $year;
    public $month;
    public $day;
    public $utc = false;
    public function __construct() {
        $args_num = func_num_args();
        $args = func_get_args();
        switch ($args_num) {
            case 0:
                $time = getdate();
                $this->year = $time['year'];
                $this->month = $time['mon'];
                $this->day = $time['mday'];
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
                }
                elseif ($args[0] instanceof HproseDate) {
                    $this->year = $args[0]->year;
                    $this->month = $args[0]->month;
                    $this->day = $args[0]->day;
                }
                else {
                    throw new HproseException('Unexpected arguments');
                }
                break;
            case 4:
                $this->utc = $args[3];
            case 3:
                if (!self::isValidDate($args[0], $args[1], $args[2])) {
                    throw new HproseException('Unexpected arguments');
                }
                $this->year = $args[0];
                $this->month = $args[1];
                $this->day = $args[2];
                break;
            default:
                throw new HproseException('Unexpected arguments');
        }
    }
    public function addDays($days) {
        if (!is_int($days)) return false;
        $year = $this->year;
        if ($days == 0) return true;
        if ($days >= 146097 || $days <= -146097) {
            $remainder = $days % 146097;
            if ($remainder < 0) {
                $remainder += 146097;
            }
            $years = 400 * (int)(($days - $remainder) / 146097);
            $year += $years;
            if ($year < 1 || $year > 9999) return false;
            $days = $remainder;
        }
        if ($days >= 36524 || $days <= -36524) {
            $remainder = $days % 36524;
            if ($remainder < 0) {
                $remainder += 36524;
            }
            $years = 100 * (int)(($days - $remainder) / 36524);
            $year += $years;
            if ($year < 1 || $year > 9999) return false;
            $days = $remainder;
        }
        if ($days >= 1461 || $days <= -1461) {
            $remainder = $days % 1461;
            if ($remainder < 0) {
                $remainder += 1461;
            }
            $years = 4 * (int)(($days - $remainder) / 1461);
            $year += $years;
            if ($year < 1 || $year > 9999) return false;
            $days = $remainder;
        }
        $month = $this->month;
        while ($days >= 365) {
            if ($year >= 9999) return false;
            if ($month <= 2) {
                if ((($year % 4) == 0) ? (($year % 100) == 0) ? (($year % 400) == 0) : true : false) {
                    $days -= 366;
                }
                else {
                    $days -= 365;
                }
                $year++;
            }
            else {
                $year++;
                if ((($year % 4) == 0) ? (($year % 100) == 0) ? (($year % 400) == 0) : true : false) {
                    $days -= 366;
                }
                else {
                    $days -= 365;
                }
            }
        }
        while ($days < 0) {
            if ($year <= 1) return false;
            if ($month <= 2) {
                $year--;
                if ((($year % 4) == 0) ? (($year % 100) == 0) ? (($year % 400) == 0) : true : false) {
                    $days += 366;
                }
                else {
                    $days += 365;
                }
            }
            else {
                if ((($year % 4) == 0) ? (($year % 100) == 0) ? (($year % 400) == 0) : true : false) {
                    $days += 366;
                }
                else {
                    $days += 365;
                }
                $year--;
            }
        }
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $day = $this->day;
        while ($day + $days > $daysInMonth) {
            $days -= $daysInMonth - $day + 1;
            $month++;
            if ($month > 12) {
                if ($year >= 9999) return false;
                $year++;
                $month = 1;
            }
            $day = 1;
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        }
        $day += $days;
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
        return true;
    }
    public function addMonths($months) {
        if (!is_int($months)) return false;
        if ($months == 0) return true;
        $month = $this->month + $months;
        $months = ($month - 1) % 12 + 1;
        if ($months < 1) {
            $months += 12;
        }
        $years = (int)(($month - $months) / 12);
        if ($this->addYears($years)) {
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $months, $this->year);
            if ($this->day > $daysInMonth) {
                $months++;
                $this->day -= $daysInMonth;
            }
            $this->month = (int)$months;
            return true;
        }
        else {
            return false;
        }
    }
    public function addYears($years) {
        if (!is_int($years)) return false;
        if ($years == 0) return true;
        $year = $this->year + $years;
        if ($year < 1 || $year > 9999) return false;
        $this->year = $year;
        return true;
    }
    public function timestamp() {
        if ($this->utc) {
            return gmmktime(0, 0, 0, $this->month, $this->day, $this->year);
        }
        else {
            return mktime(0, 0, 0, $this->month, $this->day, $this->year);
        }
    }
    public function toString($fullformat = true) {
        $format = ($fullformat ? '%04d-%02d-%02d': '%04d%02d%02d');
        $str = sprintf($format, $this->year, $this->month, $this->day);
        if ($this->utc) {
            $str .= 'Z';
        }
        return $str;
    }
    public function __toString() {
        return $this->toString();
    }

    public static function isLeapYear($year) {
        return (($year % 4) == 0) ? (($year % 100) == 0) ? (($year % 400) == 0) : true : false;
    }
    public static function daysInMonth($year, $month) {
        if (($month < 1) || ($month > 12)) {
            return false;
        }
        return cal_days_in_month(CAL_GREGORIAN, $month, $year);
    }
    public static function isValidDate($year, $month, $day) {
        if (($year >= 1) && ($year <= 9999)) {
            return checkdate($month, $day, $year);
        }
        return false;
    }

    public function dayOfWeek() {
        $num = func_num_args();
        if ($num == 3) {
            $args = func_get_args();
            $y = $args[0];
            $m = $args[1];
            $d = $args[2];
        }
        else {
            $y = $this->year;
            $m = $this->month;
            $d = $this->day;
        }
        $d += $m < 3 ? $y-- : $y - 2;
        return ((int)(23 * $m / 9) + $d + 4 + (int)($y / 4) - (int)($y / 100) + (int)($y / 400)) % 7;
    }
    public function dayOfYear() {
        static $daysToMonth365 = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334, 365);
        static $daysToMonth366 = array(0, 31, 60, 91, 121, 152, 182, 213, 244, 274, 305, 335, 366);
        $num = func_num_args();
        if ($num == 3) {
            $args = func_get_args();
            $y = $args[0];
            $m = $args[1];
            $d = $args[2];
        }
        else {
            $y = $this->year;
            $m = $this->month;
            $d = $this->day;
        }
        $days = self::isLeapYear($y) ? $daysToMonth365 : $daysToMonth366;
        return $days[$m - 1] + $d;
    }
}