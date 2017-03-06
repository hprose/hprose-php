<?php

use Hprose\Filter;

class SizeFilter implements Filter {
    private $message;
    public function __construct($message) {
        $this->message = $message;
    }
    public function inputFilter($data, stdClass $context) {
        error_log($this->message . ' input size: ' . strlen($data));
        return $data;
    }
    public function outputFilter($data, stdClass $context) {
        error_log($this->message . ' output size: ' . strlen($data));
        return $data;
    }
}