<?php

class Calculator {
    public function add($a, $b) {
        return $a + $b;
    }
    public function sub($a, $b) {
        return $a - $b;
    }
    public function mul($a, $b) {
        return $a * $b;
    }
    public function div($a, $b) {
        return $a / $b;
    }
}

class PromiseTest extends PHPUnit_Framework_TestCase {
    public function testValue() {
        $self = $this;
        $promise = \Hprose\Future\value("hello");
        $promise->done(function($result) use ($self) {
            $self->assertEquals($result, "hello");
        });
    }
    public function testError() {
        $self = $this;
        $promise = \Hprose\Future\error(new Exception("test"));
        $promise->fail(function($reason) use ($self) {
            $self->assertEquals($reason->getMessage(), "test");
        });
    }
    public function testDeferredResolve() {
        $self = $this;
        $deferred = \Hprose\deferred();
        $deferred->promise->done(function($result) use ($self) {
            $self->assertEquals($result, "hello");
        });
        $deferred->resolve("hello");
    }
    public function testDeferredReject() {
        $self = $this;
        $deferred = \Hprose\deferred();
        $deferred->promise->fail(function($reason) use ($self) {
            $self->assertEquals($reason->getMessage(), "test");
        });
        $deferred->reject(new Exception("test"));
    }
    public function testIsFuture() {
        $deferred = \Hprose\deferred();
        $this->assertEquals(\Hprose\Future\isFuture($deferred->promise), true);
        $this->assertEquals(\Hprose\Future\isFuture(new \Hprose\Future()), true);
        $this->assertEquals(\Hprose\Future\isFuture(\Hprose\Future\value("hello")), true);
        $this->assertEquals(\Hprose\Future\isFuture(\Hprose\Future\error(new Exception("test"))), true);
        $this->assertEquals(\Hprose\Future\isFuture(0), false);
    }
    public function testSync() {
        $self = $this;
        $promise = \Hprose\Future\sync(function() {
            return "hello";
        });
        $promise->done(function($result) use ($self) {
            $self->assertEquals($result, "hello");
        });
    }
    public function testPromise() {
        $self = $this;
        $promise = \Hprose\Future\promise(function($resolve, $reject) {
            $resolve(100);
        });
        $promise->done(function($result) use ($self) {
            $self->assertEquals($result, 100);
        });
        $promise = \Hprose\Future\promise(function($resolve, $reject) {
            $reject(new Exception("test"));
        });
        $promise->fail(function($reason) use ($self) {
            $self->assertEquals($reason->getMessage(), "test");
        });
    }
    public function testToFuture() {
        $self = $this;
        $promise = \Hprose\Future\value(100);
        $this->assertEquals($promise, \Hprose\Future\toFuture($promise));
        \Hprose\Future\toFuture(100)->done(function($result) use ($self) {
            $self->assertEquals($result, 100);
        });
    }
    public function testAll() {
        $self = $this;
        $p1 = \Hprose\Future\value(100);
        $p2 = \Hprose\Future\value(200);
        $p3 = \Hprose\Future\value(300);
        $all = \Hprose\Future\all(array($p1, $p2, $p3));
        $all->done(function($result) use ($self) {
            $self->assertEquals($result, array(100, 200, 300));
        });
    }
    public function testJoin() {
        $self = $this;
        $p1 = \Hprose\Future\value(100);
        $p2 = \Hprose\Future\value(200);
        $p3 = \Hprose\Future\value(300);
        $all = \Hprose\Future\join($p1, $p2, $p3);
        $all->done(function($result) use ($self) {
            $self->assertEquals($result, array(100, 200, 300));
        });
    }
    public function testSettle() {
        $self = $this;
        $p1 = \Hprose\Future\value(100);
        $p2 = \Hprose\Future\error(new Exception('test'));
        $p = \Hprose\Future\settle(array($p1, $p2));
        $p->done(function($result) use ($self) {
            $self->assertEquals($result, array(
                array(
                    "state" => "fulfilled",
                    "value" => 100
                ),
                array(
                    "state" => "rejected",
                    "reason" => new Exception('test')
                )
            ));
        });
    }
    public function testRun() {
        $self = $this;
        $sum = function($a, $b) {
            return $a + $b;
        };
        $p = \Hprose\Future\run($sum, \Hprose\Future\value(100), \Hprose\Future\value(200));
        $p->done(function($result) use ($self) {
            $self->assertEquals($result, 300);
        });
    }
    public function testWarp() {
        $self = $this;
        $sum = \Hprose\Future\wrap(function($a, $b) {
            return $a + $b;
        });
        $p = $sum(\Hprose\Future\value(100), \Hprose\Future\value(200));
        $p->done(function($result) use ($self) {
            $self->assertEquals($result, 300);
        });
    }
    public function testWarp2() {
        $self = $this;
        $calculator = \Hprose\Future\wrap(new Calculator());
        $p1 = $calculator->add(\Hprose\Future\value(100), \Hprose\Future\value(200));
        $p1->done(function($result) use ($self) {
            $self->assertEquals($result, 300);
        });
        $p2 = $calculator->sub(\Hprose\Future\value(100), \Hprose\Future\value(200));
        $p2->done(function($result) use ($self) {
            $self->assertEquals($result, -100);
        });
    }
    public function testEach() {
        $self = $this;
        $array = array();
        $n = 0;
        for ($i = 0; $i < 100; $i++) {
            $array[$i] = \Hprose\Future\value($i);
            $n += $i;
        }
        $sum = 0;
        \Hprose\Future\each($array, function($value) use (&$sum) {
            $sum += $value;
        })->done(function() use ($self, &$sum, $n) {
            $self->assertEquals($sum, $n);
        });
        $a2 = \Hprose\Future\value($array);
        $a2->each(function($value, $index) use ($self) {
            $self->assertEquals($value, $index);
        })->fail(function($reason) { throw $reason; });
    }
    public function testEvery() {
        $self = $this;
        $isBigEnough = function($element, $index, $array) {
            return $element >= 10;
        };
        $a1 = array(12, \Hprose\Future\value(5), 8, \Hprose\Future\value(130), 44);
        $a2 = array(12, \Hprose\Future\value(54), 18, \Hprose\Future\value(130), 44);
        \Hprose\Future\every($a1, $isBigEnough)->done(function($result) use ($self) {
            $self->assertEquals($result, false);
        });
        \Hprose\Future\every($a2, $isBigEnough)->done(function($result) use ($self) {
            $self->assertEquals($result, true);
        });
        $a3 = \Hprose\Future\value($a1);
        $a4 = \Hprose\Future\value($a2);
        $a3->every($isBigEnough)->done(function($result) use ($self) {
            $self->assertEquals($result, false);
        });
        $a4->every($isBigEnough)->done(function($result) use ($self) {
            $self->assertEquals($result, true);
        });
    }
    public function testSome() {
        $self = $this;
        $isBiggerThan10 = function($element, $index, $array) {
            return $element >= 10;
        };
        $a1 = array(2, \Hprose\Future\value(5), 8, \Hprose\Future\value(1), 4);
        $a2 = array(12, \Hprose\Future\value(5), 8, \Hprose\Future\value(1), 4);
        \Hprose\Future\some($a1, $isBiggerThan10)->done(function($result) use ($self) {
            $self->assertEquals($result, false);
        });
        \Hprose\Future\some($a2, $isBiggerThan10)->done(function($result) use ($self) {
            $self->assertEquals($result, true);
        });
        $a3 = \Hprose\Future\value($a1);
        $a4 = \Hprose\Future\value($a2);
        $a3->some($isBiggerThan10)->done(function($result) use ($self) {
            $self->assertEquals($result, false);
        });
        $a4->some($isBiggerThan10)->done(function($result) use ($self) {
            $self->assertEquals($result, true);
        });
    }
    public function testFilter() {
        $self = $this;
        $isBigEnough = function($element, $index, $array) {
            return $element >= 10;
        };
        $a1 = array(12, \Hprose\Future\value(5), 8, \Hprose\Future\value(130), 44);
        $a2 = \Hprose\Future\value($a1);
        \Hprose\Future\filter($a1, $isBigEnough)->done(function($result) use ($self) {
            $self->assertEquals($result, array(12, 130, 44));
        });
        \Hprose\Future\filter($a1, $isBigEnough, true)->done(function($result) use ($self) {
            $self->assertEquals($result, array(0=>12, 3=>130, 4=>44));
        });
        $a2->filter($isBigEnough)->done(function($result) use ($self) {
            $self->assertEquals($result, array(12, 130, 44));
        });
        $a2->filter($isBigEnough, true)->done(function($result) use ($self) {
            $self->assertEquals($result, array(0=>12, 3=>130, 4=>44));
        });
    }
    public function testMap() {
        $self = $this;
        $double = function($n) {
            return $n * 2;
        };
        $a1 = array(\Hprose\Future\value(1), 4, \Hprose\Future\value(9));
        $a2 = \Hprose\Future\value($a1);
        \Hprose\Future\map($a1, "sqrt")->done(function($result) use ($self) {
            $self->assertEquals($result, array(1, 2, 3));
        });
        \Hprose\Future\map($a1, $double)->done(function($result) use ($self) {
            $self->assertEquals($result, array(2, 8, 18));
        });
        $a2->map("sqrt")->done(function($result) use ($self) {
            $self->assertEquals($result, array(1, 2, 3));
        });
        $a2->map($double)->done(function($result) use ($self) {
            $self->assertEquals($result, array(2, 8, 18));
        });
    }
    public function testReduce() {
        $self = $this;
        $sum = function($a, $b) {
            return $a + $b;
        };
        $a1 = array(\Hprose\Future\value(0), 1, \Hprose\Future\value(2), 3, \Hprose\Future\value(4));
        $a2 = \Hprose\Future\value($a1);
        \Hprose\Future\reduce($a1, $sum)->done(function($result) use ($self) {
            $self->assertEquals($result, 10);
        });
        \Hprose\Future\reduce($a1, $sum, 10)->done(function($result) use ($self) {
            $self->assertEquals($result, 20);
        });
        $a2->reduce($sum)->done(function($result) use ($self) {
            $self->assertEquals($result, 10);
        });
        $a2->reduce($sum, 10)->done(function($result) use ($self) {
            $self->assertEquals($result, 20);
        });
    }
    public function testSearch() {
        $self = $this;
        $a1 = array(\Hprose\Future\value(0), 12, \Hprose\Future\value(24), 36, \Hprose\Future\value(48));
        $a2 = \Hprose\Future\value($a1);
        \Hprose\Future\search($a1, 24)->done(function($result) use ($self) {
            $self->assertEquals($result, 2);
        });
        \Hprose\Future\search($a1, \Hprose\Future\value(36))->done(function($result) use ($self) {
            $self->assertEquals($result, 3);
        });
        $a2->search(24)->done(function($result) use ($self) {
            $self->assertEquals($result, 2);
        });
        $a2->search(\Hprose\Future\value(36))->done(function($result) use ($self) {
            $self->assertEquals($result, 3);
        });
    }
    public function testIncludes() {
        $self = $this;
        $a1 = array(\Hprose\Future\value(0), 12, \Hprose\Future\value(24), 36, \Hprose\Future\value(48));
        $a2 = \Hprose\Future\value($a1);
        \Hprose\Future\includes($a1, 21)->done(function($result) use ($self) {
            $self->assertEquals($result, false);
        });
        \Hprose\Future\includes($a1, \Hprose\Future\value(36))->done(function($result) use ($self) {
            $self->assertEquals($result, true);
        });
        $a2->includes(24)->done(function($result) use ($self) {
            $self->assertEquals($result, true);
        });
        $a2->includes(\Hprose\Future\value(35))->done(function($result) use ($self) {
            $self->assertEquals($result, false);
        });
    }
    public function testDiff() {
        $self = $this;
        $assertEquals = \Hprose\Future\wrap(array($this, "assertEquals"));
        $a1 = array(\Hprose\Future\value(0), 12, \Hprose\Future\value(24), 36, \Hprose\Future\value(48));
        $a2 = array(\Hprose\Future\value(12), 2, \Hprose\Future\value(36), 48, \Hprose\Future\value(50));
        \Hprose\Future\diff($a1, $a2)->done(function($result) use ($self) {
            $self->assertEquals($result, array(0=>0, 2=>24));
        });
        \Hprose\Future\run('array_diff', \Hprose\Future\all($a1), \Hprose\Future\all($a2))->done(function($result) use ($self) {
            $self->assertEquals($result, array(0=>0, 2=>24));
        });
    }
    public function testFutureResolve() {
        $self = $this;
        $p = new \Hprose\Future();
        $p->done(function($result) use ($self) {
            $self->assertEquals($result, 100);
        });
        $p->resolve(100);
    }
    public function testFutureReject() {
        $self = $this;
        $p = new \Hprose\Future();
        $p->fail(function($reason) use ($self) {
            $self->assertEquals($reason->getMessage(), "test");
        });
        $p->reject(new Exception("test"));
    }
    public function testFutureInspect() {
        $self = $this;
        $p1 = \Hprose\Future\value(100);
        $p2 = \Hprose\Future\reject(new Exception("test"));
        $p3 = new \Hprose\Future();
        $this->assertEquals($p1->inspect(), array('state' => 'fulfilled', 'value' => 100));
        $this->assertEquals($p2->inspect(), array('state' => 'rejected', 'reason' => new Exception("test")));
        $this->assertEquals($p3->inspect(), array('state' => 'pending'));
    }
    public function testFutureAlways() {
        $self = $this;
        $p1 = \Hprose\Future\value(100);
        $p2 = \Hprose\Future\reject(new Exception("test"));
        $p1->always(function($result) use ($self) {
            $self->assertEquals($result, 100);
        });
        $p2->always(function($result) use ($self) {
            $self->assertEquals($result, new Exception("test"));
        });
    }
    public function testFutureTap() {
        $self = $this;
        $p = \Hprose\Future\value(100);
        $p->tap('print_r')->done(function($result) use ($self) {
            $self->assertEquals($result, 100);
        });
    }
    public function testFutureSpread() {
        $self = $this;
        $sum = function($a, $b) { return $a + $b; };
        $p = \Hprose\Future\value(array(100, 200));
        $p->spread($sum)->done(function($result) use ($self) {
            $self->assertEquals($result, 300);
        });
    }
    public function testFutureGet() {
        $self = $this;
        $o = new \stdClass();
        $o->name = "Tom";
        $p = \Hprose\Future\value($o);
        $p->name->done(function($result) use ($self) {
            $self->assertEquals($result, "Tom");
        });
    }
}
