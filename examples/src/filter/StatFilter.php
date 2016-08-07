<?php

use Hprose\Filter;

class StatFilter implements Filter {
    private function stat(stdClass $context) {
        if (isset($context->userdata->starttime)) {
            $t = microtime(true) - $context->userdata->starttime;
            error_log("It takes $t s.");
        }
        else {
            $context->userdata->starttime = microtime(true);
        }
    }
    public function inputFilter($data, stdClass $context) {
        $this->stat($context);
        return $data;
    }
    public function outputFilter($data, stdClass $context) {
        $this->stat($context);
        return $data;
    }
}