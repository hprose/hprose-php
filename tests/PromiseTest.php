<?php
class PromiseTest extends PHPUnit_Framework_TestCase {
    public function testValue() {
        $promise = \Hprose\Future\value("hello");
        $self = $this;
        $promise->then(function($result) use ($self) {
            $self->assertEquals($result, "hello");
        });
    }
    public function testError() {
        $promise = \Hprose\Future\error(new Exception("test"));
        $self = $this;
        $promise->then(NULL, function($reason) use ($self) {
            $self->assertEquals($reason->getMessage(), "test");
        });
        $promise->catchError(function($reason) use ($self) {
            $self->assertEquals($reason->getMessage(), "test");
        });
    }
    public function testDeferredResolve() {
        $deferred = \Hprose\deferred();
        $self = $this;
        $deferred->promise->then(function($result) use ($self) {
            $self->assertEquals($result, "hello");
        });
        $deferred->resolve("hello");
    }
    public function testDeferredReject() {
        $deferred = \Hprose\deferred();
        $self = $this;
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
    public function testDelayed() {
        $promise = \Hprose\Future\delayed(0.3, function() {
            return "promise from Future.delayed";
        });
        $self = $this;
        $promise->then(function($result) use ($self) {
            $self->assertEquals($result, "promise from Future.delayed");
        });
    }
}
