<?php
class PromiseTest extends PHPUnit_Framework_TestCase {
    public function testDelayed() {
    $self = $this;
        $promise = \Hprose\Future\delayed(0.3, function() {
            return "promise from Future.delayed";
        });
        $promise->then(function($result) use ($self) {
            $self->assertEquals($result, "promise from Future.delayed");
        });
    }
    public function testValue() {
        $self = $this;
        $promise = \Hprose\Future\value("hello");
        $promise->then(function($result) use ($self) {
            $self->assertEquals($result, "hello");
        });
    }
    public function testError() {
        $self = $this;
        $promise = \Hprose\Future\error(new Exception("test"));
        $promise->then(NULL, function($reason) use ($self) {
            $self->assertEquals($reason->getMessage(), "test");
        });
        $promise->catchError(function($reason) use ($self) {
            $self->assertEquals($reason->getMessage(), "test");
        });
    }
    public function testDeferredResolve() {
        $self = $this;
        $deferred = \Hprose\deferred();
        $deferred->promise->then(function($result) use ($self) {
            $self->assertEquals($result, "hello");
        });
        $deferred->resolve("hello");
    }
    public function testDeferredReject() {
        $self = $this;
        $deferred = \Hprose\deferred();
        $deferred->promise->catchError(function($reason) use ($self) {
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
        $promise->then(function($result) use ($self) {
            $self->assertEquals($result, "hello");
        });
    }
    public function testPromise() {
        $self = $this;
        $promise = \Hprose\Future\promise(function($resolve, $reject) {
            $resolve(100);
        });
        $promise->then(function($result) use ($self) {
            $self->assertEquals($result, 100);
        });
        $promise = \Hprose\Future\promise(function($resolve, $reject) {
            $reject(new Exception("test"));
        });
        $promise->catchError(function($reason) use ($self) {
            $self->assertEquals($reason->getMessage(), "test");
        });
    }
    public function testToFuture() {
        $self = $this;
        $promise = \Hprose\Future\value(100);
        $this->assertEquals($promise, \Hprose\Future\toFuture($promise));
        \Hprose\Future\toFuture(100)->then(function($result) use ($self) {
            $self->assertEquals($result, 100);
        });
    }
    public function testAll() {
        $self = $this;
        $p1 = \Hprose\Future\value(100);
        $p2 = \Hprose\Future\value(200);
        $p3 = \Hprose\Future\value(300);
        $all = \Hprose\Future\all(array($p1, $p2, $p3));
        $all->then(function($result) use ($self) {
            $self->assertEquals($result, array(100, 200, 300));
        });
    }
    public function testJoin() {
        $self = $this;
        $p1 = \Hprose\Future\value(100);
        $p2 = \Hprose\Future\value(200);
        $p3 = \Hprose\Future\value(300);
        $all = \Hprose\Future\join($p1, $p2, $p3);
        $all->then(function($result) use ($self) {
            $self->assertEquals($result, array(100, 200, 300));
        });
    }
    public function testRace() {
        $self = $this;
        $p1 = \Hprose\Future\delayed(0.3, 100);
        $p2 = \Hprose\Future\delayed(0.2, 200);
        $p3 = \Hprose\Future\delayed(0.1, 300);
        $p = \Hprose\Future\race(array($p1, $p2, $p3));
        $p->then(function($result) use ($self) {
            $self->assertEquals($result, 300);
        });
        $p4 = \Hprose\Future\error(new Exception('test'));
        $p = \Hprose\Future\race(array($p4));
        $p->catchError(function($reason) use ($self) {
            $self->assertEquals($reason->getMessage(), 'test');
        });
    }
    public function testAny() {
        $self = $this;
        $p1 = \Hprose\Future\delayed(0.3, 100);
        $p2 = \Hprose\Future\delayed(0.2, 200);
        $p3 = \Hprose\Future\delayed(0.1, 300);
        $p = \Hprose\Future\any(array($p1, $p2, $p3));
        $p->then(function($result) use ($self) {
            $self->assertEquals($result, 300);
        });
        $p = \Hprose\Future\any(array());
        $p->catchError(function($reason) use ($self) {
            $self->assertEquals($reason->getMessage(), 'any(): $array must not be empty');
        });
        $p4 = \Hprose\Future\error(new Exception('test'));
        $p = \Hprose\Future\any(array($p1, $p2, $p3, $p4));
        $p->then(function($result) use ($self) {
            $self->assertEquals($result, 300);
        });
        $p = \Hprose\Future\any(array($p4));
        $p->catchError(function($reasons) use ($self) {
            $self->assertEquals($reasons[0]->getMessage(), 'test');
        });
    }
    public function testSettle() {
        $self = $this;
        $p1 = \Hprose\Future\value(100);
        $p2 = \Hprose\Future\error(new Exception('test'));
        $p = \Hprose\Future\settle(array($p1, $p2));
        $p->then(function($result) use ($self) {
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
        $p->then(function($result) use ($self) {
            $self->assertEquals($result, 300);
        });
    }
    public function testWarp() {
        $sum = \Hprose\Future\wrap(function($a, $b) {
            return $a + $b;
        });
        $assertEquals = \Hprose\Future\wrap(array($this, "assertEquals"));
        $assertEquals($sum(\Hprose\Future\value(100), \Hprose\Future\value(200)), 300);
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
        })->then(function() use ($self, $sum, $n) {
            $self->assertEquals($sum, $n);
        });
        $a2 = \Hprose\Future\value($array);
        $a2->each(function($value, $index) use ($self) {
            $self->assertEquals($value, $index);
        });
    }
    public function testEvery() {
        $isBigEnough = function($element, $index, $array) {
            return $element >= 10;
        };
        $assertEquals = \Hprose\Future\wrap(array($this, "assertEquals"));
        $a1 = array(12, \Hprose\Future\value(5), 8, \Hprose\Future\value(130), 44);
        $a2 = array(12, \Hprose\Future\value(54), 18, \Hprose\Future\value(130), 44);
        $assertEquals(\Hprose\Future\every($a1, $isBigEnough), false);
        $assertEquals(\Hprose\Future\every($a2, $isBigEnough), true);
        $a3 = \Hprose\Future\value($a1);
        $a4 = \Hprose\Future\value($a2);
        $assertEquals($a3->every($isBigEnough), false);
        $assertEquals($a4->every($isBigEnough), true);
    }
    public function testSome() {
        $isBiggerThan10 = function($element, $index, $array) {
            return $element >= 10;
        };
        $assertEquals = \Hprose\Future\wrap(array($this, "assertEquals"));
        $a1 = array(2, \Hprose\Future\value(5), 8, \Hprose\Future\value(1), 4);
        $a2 = array(12, \Hprose\Future\value(5), 8, \Hprose\Future\value(1), 4);
        $assertEquals(\Hprose\Future\some($a1, $isBiggerThan10), false);
        $assertEquals(\Hprose\Future\some($a2, $isBiggerThan10), true);
        $a3 = \Hprose\Future\value($a1);
        $a4 = \Hprose\Future\value($a2);
        $assertEquals($a3->some($isBiggerThan10), false);
        $assertEquals($a4->some($isBiggerThan10), true);
    }
    public function testFilter() {
        $isBigEnough = function($element, $index, $array) {
            return $element >= 10;
        };
        $assertEquals = \Hprose\Future\wrap(array($this, "assertEquals"));
        $a1 = array(12, \Hprose\Future\value(5), 8, \Hprose\Future\value(130), 44);
        $a2 = \Hprose\Future\value($a1);
        $assertEquals(\Hprose\Future\filter($a1, $isBigEnough), array(12, 130, 44));
        $assertEquals(\Hprose\Future\filter($a1, $isBigEnough, true), array(0=>12, 3=>130, 4=>44));
        $assertEquals($a2->filter($isBigEnough), array(12, 130, 44));
        $assertEquals($a2->filter($isBigEnough, true), array(0=>12, 3=>130, 4=>44));
    }
}
