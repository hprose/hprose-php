<?php

use Hprose\RPC\Core\Client;
use Hprose\RPC\Core\MockServer;
use Hprose\RPC\Core\Service;
use Hprose\RPC\Plugins\ExecuteTimeoutHandler;

class MockTest extends PHPUnit_Framework_TestCase {
    public function testHelloWorld() {
        $service = new Service();
        $service->addCallable(function ($name) {
            return 'hello ' . $name;
        }, 'hello');
        $server = new MockServer('test');
        $service->bind($server);
        $client = new Client(['mock://test']);
        $proxy = $client->useService();
        $result = $proxy->hello('world');
        $this->assertEquals($result, 'hello world');
        $server->close();
    }
    public function testClientTimeout() {
        $service = new Service();
        $service->addCallable(function ($time) {
            sleep($time);
        }, 'wait');
        $server = new MockServer('test');
        $service->bind($server);
        $client = new Client(['mock://test']);
        $client->timeout = 1000;
        $proxy = $client->useService();
        try {
            $proxy->wait(2);
            $this->assertEquals('ok', 'fuck');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'timeout');
        }
        $server->close();
    }
    public function testServiceTimeout() {
        $service = new Service();
        $service->addCallable(function ($time) {
            sleep($time);
        }, 'wait');
        $service->use([new ExecuteTimeoutHandler(1000), 'handler']);
        $server = new MockServer('test');
        $service->bind($server);
        $client = new Client(['mock://test']);
        $proxy = $client->useService();
        try {
            $proxy->wait(2);
            $this->assertEquals('ok', 'fuck');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'timeout');
        }
        $server->close();
    }

}