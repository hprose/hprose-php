<?php

use Hprose\Filter;

class CompressFilter implements Filter {
    public function inputFilter($data, stdClass $context) {
        return gzdecode($data);
    }
    public function outputFilter($data, stdClass $context) {
        return gzencode($data);
    }
}
