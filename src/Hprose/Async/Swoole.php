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
 * Hprose/Async/Swoole.php                                *
 *                                                        *
 * asynchronous functions base on swoole for php 5.3+     *
 *                                                        *
 * LastModified: Jul 8, 2016                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Async;

class Swoole extends Base {
    const MILLISECONDS_PER_SECOND = 1000;
    private $n = 0;
    private function stopTimer() {
        $this->n--;
        if ($this->n === 0) {
            swoole_event_exit();
        }
    }
    function nextTick($func) {
        $self = $this;
        $this->n++;
        $args = array_slice(func_get_args(), 1);
        $task = function() use ($self, $func, $args) {
            try {
                call_user_func_array($func, $args);
            }
            catch (\Exception $e) {
                $self->stopTimer();
                throw $e;
            }
            catch (\Throwable $e) {
                $self->stopTimer();
                throw $e;
            }
            $self->stopTimer();
        };
        return swoole_timer_after(1, $task);
    }
    protected function setTimer($func, $delay, $loop, $args) {
        $delay = $delay * self::MILLISECONDS_PER_SECOND;
        $this->n++;
        if ($loop) {
            $timer = swoole_timer_tick($delay, function() use($func, $args) {
                call_user_func_array($func, $args);
            });
        }
        else {
            $self = $this;
            $timer = swoole_timer_after($delay, function() use($self, $func, $args) {
                try {
                    call_user_func_array($func, $args);
                }
                catch (\Exception $e) {
                    $self->stopTimer();
                    throw $e;
                }
                catch (\Throwable $e) {
                    $self->stopTimer();
                    throw $e;
                }
                $self->stopTimer();
            });
        }
        return $timer;
    }
    protected function clearTimer($timer) {
        if (@swoole_timer_clear($timer)) {
            $this->stopTimer();
        }
    }
    function loop() {
        swoole_event_wait();
    }
}
