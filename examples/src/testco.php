<?php
require_once "../vendor/autoload.php";

use Hprose\Promise;

function wait() {
    return new Promise(function($reslove, $reject) {
        swoole_timer_after(1, function() use (&$reslove) {
            $reslove();
        });
    });
}

gc_enable();

Promise\co(function() {
    for ($i = 0; true; $i++) {
        yield wait();
        if ($i % 10000 === 0) {
            gc_collect_cycles();
            var_dump(memory_get_usage(true), time());
        }
    }
})->then(function() {
    var_dump('finished');
}, function($e) {
    var_dump($e);
});
