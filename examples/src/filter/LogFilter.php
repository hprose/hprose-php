<?php
use Hprose\Filter;

class LogFilter implements Filter {
    public function inputFilter($data, stdClass $context) {
        error_log($data);
        return $data;
    }
    public function outputFilter($data, stdClass $context) {
        error_log($data);
        return $data;
    }
}
