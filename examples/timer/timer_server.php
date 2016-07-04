<?php
    require_once('../../src/Hprose.php');
    class TimerService {
        private $queue = array();
        function startTimer() {
            $self = $this;
            swoole_timer_tick(1000, function() use ($self) {
                $future = array_pop($self->queue);
                if ($future != NULL) {
                    echo time();
                    $future->resolve(time());
                }
            });
        }
        function timer($id) {
            $future = new \Hprose\Future();
            $this->queue[] = $future;
            return $future;
        }
    }
    $timer = new TimerService();
    $server = new HproseSwooleServer("ws://0.0.0.0:8080");
    $server->setErrorTypes(E_ALL);
    $server->setDebugEnabled();
    $server->add($timer);
    $server->server->on('WorkerStart', array($timer, "startTimer"));
    $server->start();
?>
