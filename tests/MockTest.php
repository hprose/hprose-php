<?php
declare (strict_types = 1);

use Hprose\RPC\Client;
use Hprose\RPC\Core\ClientContext;
use Hprose\RPC\Core\Context;
use Hprose\RPC\Mock\MockServer;
use Hprose\RPC\Plugins\CircuitBreaker\CircuitBreaker;
use Hprose\RPC\Plugins\CircuitBreaker\MockService;
use Hprose\RPC\Plugins\ErrorToException;
use Hprose\RPC\Plugins\ExecuteTimeout;
use Hprose\RPC\Plugins\Forward;
use Hprose\RPC\Plugins\LoadBalance\NginxRoundRobinLoadBalance;
use Hprose\RPC\Plugins\LoadBalance\RandomLoadBalance;
use Hprose\RPC\Plugins\LoadBalance\RoundRobinLoadBalance;
use Hprose\RPC\Plugins\LoadBalance\WeightedLeastActiveLoadBalance;
use Hprose\RPC\Plugins\LoadBalance\WeightedRandomLoadBalance;
use Hprose\RPC\Plugins\LoadBalance\WeightedRoundRobinLoadBalance;
use Hprose\RPC\Plugins\Log;
use Hprose\RPC\Service;

class MockTest extends \PHPUnit\Framework\TestCase {
    public function testHelloWorld() {
        $service = new Service();
        $service->addCallable(function ($name) {
            return 'hello ' . $name;
        }, 'hello');
        $server = new MockServer('testHelloWorld');
        $service->bind($server);
        $client = new Client(['mock://testHelloWorld']);
        $log = new Log();
        $client->use([$log, 'invokeHandler'], [$log, 'ioHandler']);
        $proxy = $client->useService();
        $result = $proxy->hello('world');

        $this->assertEquals($result, 'hello world');
        $server->close();
    }
    public function testClientTimeout() {
        $this->expectException('Exception');
        $this->expectExceptionMessage('timeout');
        $service = new Service();
        $service->addCallable(function ($time) {
            sleep($time);
        }, 'wait');
        $server = new MockServer('testClientTimeout');
        $service->bind($server);
        $client = new Client(['mock://testClientTimeout']);
        $client->timeout = 1;
        $proxy = $client->useService();
        $proxy->wait(30);
        $server->close();
    }
    public function testServiceTimeout() {
        $this->expectException('Exception');
        $this->expectExceptionMessage('timeout');
        $service = new Service();
        $service->addCallable(function ($time) {
            sleep($time);
        }, 'wait');
        $service->use([new ExecuteTimeout(1), 'handler'], [new Log(), 'ioHandler']);
        $server = new MockServer('testServiceTimeout');
        $service->bind($server);
        $client = new Client(['mock://testServiceTimeout']);
        $proxy = $client->useService();
        $proxy->wait(30);
        $server->close();
    }
    public function testMissingMethod() {
        $service = new Service();
        $service->addMissingMethod(function (string $name, array $args): string {
            return $name . json_encode($args);
        });
        $server = new MockServer('testMissingMethod');
        $service->bind($server);
        $client = new Client(['mock://testMissingMethod']);
        $proxy = $client->useService();
        $result = $proxy->hello('world');
        $this->assertEquals($result, 'hello["world"]');
        $server->close();
    }
    public function testMissingMethod2() {
        $service = new Service();
        $service->addMissingMethod(function (string $name, array $args, Context $context): string {
            return $name . json_encode($args) . $context->remoteAddress['address'];
        });
        $server = new MockServer('testMissingMethod2');
        $service->bind($server);
        $client = new Client(['mock://testMissingMethod2']);
        $proxy = $client->useService();
        $result = $proxy->hello('world');
        $this->assertEquals($result, 'hello["world"]testMissingMethod2');
        $server->close();
    }
    public function testHeaders() {
        $service = new Service();
        $service->addCallable(function ($name) {
            return 'hello ' . $name;
        }, 'hello');
        $service->use(function (string $fullname, array $args, Context $context, callable $next) {
            if ($fullname === 'hello') {
                $this->assertTrue($context->requestHeaders['ping']);
            }
            $result = $next($fullname, $args, $context);
            $context->responseHeaders['pong'] = true;
            return $result;
        });
        $server = new MockServer('testHeaders');
        $service->bind($server);
        $client = new Client(['mock://testHeaders']);
        $proxy = $client->useService();
        $context = new ClientContext();
        $context->requestHeaders['ping'] = true;
        $result = $proxy->hello('world', $context);
        $this->assertEquals($result, 'hello world');
        $this->assertTrue($context->responseHeaders['pong']);
        $server->close();
    }
    public function testMaxRequestLength() {
        $this->expectException('Exception');
        $this->expectExceptionMessage('Request entity too large');
        $service = new Service();
        $service->maxRequestLength = 10;
        $service->addCallable(function ($name) {
            return 'hello ' . $name;
        }, 'hello');
        $server = new MockServer('testMaxRequestLength');
        $service->bind($server);
        $client = new Client(['mock://testMaxRequestLength']);
        $proxy = $client->useService();
        $proxy->hello('world');
        $server->close();
    }
    public function testRandomLoadBalance() {
        $service = new Service();
        $service->addCallable(function (string $name, Context $context) {
            error_log($context->remoteAddress['address']);
            return 'hello ' . $name;
        }, 'hello');
        $server1 = new MockServer('testRandomLoadBalance1');
        $server2 = new MockServer('testRandomLoadBalance2');
        $server3 = new MockServer('testRandomLoadBalance3');
        $server4 = new MockServer('testRandomLoadBalance4');
        $service->bind($server1);
        $service->bind($server2);
        $service->bind($server3);
        $service->bind($server4);
        $client = new Client([
            'mock://testRandomLoadBalance1',
            'mock://testRandomLoadBalance2',
            'mock://testRandomLoadBalance3',
            'mock://testRandomLoadBalance4']);
        $loadBalance = new RandomLoadBalance();
        $client->use([$loadBalance, 'handler']);
        $proxy = $client->useService();
        for ($i = 0; $i < 10; ++$i) {
            $result = $proxy->hello('world');
        }
        $this->assertEquals($result, 'hello world');
        $server1->close();
        $server2->close();
        $server3->close();
        $server4->close();
    }
    public function testWeightedRandomLoadBalance() {
        $service = new Service();
        $service->addCallable(function (string $name, Context $context) {
            error_log($context->remoteAddress['address']);
            return 'hello ' . $name;
        }, 'hello');
        $server1 = new MockServer('testWeightedRandomLoadBalance1');
        $server2 = new MockServer('testWeightedRandomLoadBalance2');
        $server3 = new MockServer('testWeightedRandomLoadBalance3');
        $server4 = new MockServer('testWeightedRandomLoadBalance4');
        $service->bind($server1);
        $service->bind($server2);
        $service->bind($server3);
        $service->bind($server4);
        $client = new Client();
        $loadBalance = new WeightedRandomLoadBalance([
            'mock://testWeightedRandomLoadBalance1' => 1,
            'mock://testWeightedRandomLoadBalance2' => 2,
            'mock://testWeightedRandomLoadBalance3' => 3,
            'mock://testWeightedRandomLoadBalance4' => 4,
        ]);
        $client->use([$loadBalance, 'handler']);
        $proxy = $client->useService();
        for ($i = 0; $i < 10; ++$i) {
            $result = $proxy->hello('world');
        }
        $this->assertEquals($result, 'hello world');
        $server1->close();
        $server2->close();
        $server3->close();
        $server4->close();
    }
    public function testRoundRobinLoadBalance() {
        $service = new Service();
        $service->addCallable(function (string $name, Context $context) {
            error_log($context->remoteAddress['address']);
            return 'hello ' . $name;
        }, 'hello');
        $server1 = new MockServer('testRoundRobinLoadBalance1');
        $server2 = new MockServer('testRoundRobinLoadBalance2');
        $server3 = new MockServer('testRoundRobinLoadBalance3');
        $server4 = new MockServer('testRoundRobinLoadBalance4');
        $service->bind($server1);
        $service->bind($server2);
        $service->bind($server3);
        $service->bind($server4);
        $client = new Client([
            'mock://testRoundRobinLoadBalance1',
            'mock://testRoundRobinLoadBalance2',
            'mock://testRoundRobinLoadBalance3',
            'mock://testRoundRobinLoadBalance4']);
        $loadBalance = new RoundRobinLoadBalance();
        $client->use([$loadBalance, 'handler']);
        $proxy = $client->useService();
        for ($i = 0; $i < 10; ++$i) {
            $result = $proxy->hello('world');
        }
        $this->assertEquals($result, 'hello world');
        $server1->close();
        $server2->close();
        $server3->close();
        $server4->close();
    }
    public function testWeightedRoundRobinLoadBalance() {
        $service = new Service();
        $service->addCallable(function (string $name, Context $context) {
            error_log($context->remoteAddress['address']);
            return 'hello ' . $name;
        }, 'hello');
        $server1 = new MockServer('testWeightedRoundRobinLoadBalance1');
        $server2 = new MockServer('testWeightedRoundRobinLoadBalance2');
        $server3 = new MockServer('testWeightedRoundRobinLoadBalance3');
        $server4 = new MockServer('testWeightedRoundRobinLoadBalance4');
        $service->bind($server1);
        $service->bind($server2);
        $service->bind($server3);
        $service->bind($server4);
        $client = new Client();
        $loadBalance = new WeightedRoundRobinLoadBalance([
            'mock://testWeightedRoundRobinLoadBalance1' => 1,
            'mock://testWeightedRoundRobinLoadBalance2' => 2,
            'mock://testWeightedRoundRobinLoadBalance3' => 3,
            'mock://testWeightedRoundRobinLoadBalance4' => 4,
        ]);
        $client->use([$loadBalance, 'handler']);
        $proxy = $client->useService();
        for ($i = 0; $i < 10; ++$i) {
            $result = $proxy->hello('world');
        }
        $this->assertEquals($result, 'hello world');
        $server1->close();
        $server2->close();
        $server3->close();
        $server4->close();
    }
    public function testNginxRoundRobinLoadBalance() {
        $service = new Service();
        $service->addCallable(function (string $name, Context $context) {
            error_log($context->remoteAddress['address']);
            return 'hello ' . $name;
        }, 'hello');
        $server1 = new MockServer('testNginxRoundRobinLoadBalance1');
        $server2 = new MockServer('testNginxRoundRobinLoadBalance2');
        $server3 = new MockServer('testNginxRoundRobinLoadBalance3');
        $server4 = new MockServer('testNginxRoundRobinLoadBalance4');
        $service->bind($server1);
        $service->bind($server2);
        $service->bind($server3);
        $service->bind($server4);
        $client = new Client();
        $loadBalance = new NginxRoundRobinLoadBalance([
            'mock://testNginxRoundRobinLoadBalance1' => 1,
            'mock://testNginxRoundRobinLoadBalance2' => 2,
            'mock://testNginxRoundRobinLoadBalance3' => 3,
            'mock://testNginxRoundRobinLoadBalance4' => 4,
        ]);
        $client->use([$loadBalance, 'handler']);
        $proxy = $client->useService();
        for ($i = 0; $i < 10; ++$i) {
            $result = $proxy->hello('world');
        }
        $this->assertEquals($result, 'hello world');
        $server1->close();
        $server2->close();
        $server3->close();
        $server4->close();
    }
    public function testWeightedLeastActiveLoadBalance() {
        $service = new Service();
        $service->addCallable(function (string $name, Context $context) {
            error_log($context->remoteAddress['address']);
            return 'hello ' . $name;
        }, 'hello');
        $server1 = new MockServer('testWeightedLeastActiveLoadBalance1');
        $server2 = new MockServer('testWeightedLeastActiveLoadBalance2');
        $server3 = new MockServer('testWeightedLeastActiveLoadBalance3');
        $server4 = new MockServer('testWeightedLeastActiveLoadBalance4');
        $service->bind($server1);
        $service->bind($server2);
        $service->bind($server3);
        $service->bind($server4);
        $client = new Client();
        $loadBalance = new WeightedLeastActiveLoadBalance([
            'mock://testWeightedLeastActiveLoadBalance1' => 1,
            'mock://testWeightedLeastActiveLoadBalance2' => 2,
            'mock://testWeightedLeastActiveLoadBalance3' => 3,
            'mock://testWeightedLeastActiveLoadBalance4' => 4,
        ]);
        $client->use([$loadBalance, 'handler']);
        $proxy = $client->useService();
        for ($i = 0; $i < 10; ++$i) {
            $result = $proxy->hello('world');
        }
        $this->assertEquals($result, 'hello world');
        $server1->close();
        $server2->close();
        $server3->close();
        $server4->close();
    }
    public function testCircuitBreaker() {
        $client = new Client(['mock://CircuitBreaker']);
        $circuitBreaker = new CircuitBreaker();
        $circuitBreaker->mockService = new class implements MockService {
            public function invoke(string $name, array &$args, Context $context) {
                return $name;
            }
        };
        $client->use([$circuitBreaker, 'invokeHandler'], [$circuitBreaker, 'ioHandler']);
        $proxy = $client->useService();
        for ($i = 0; $i < 10; ++$i) {
            try {
                $result = $proxy->hello();
            } catch (Throwable $e) {}
        }
        $this->assertEquals($result, 'hello');
    }
    public function testErrorToException() {
        $error_handler = set_error_handler(NULL);
        try {
            $this->expectException('Exception');
            $this->expectExceptionMessage('Undefined variable: i');
            $service = new Service();
            $service->addCallable(function ($name) {
                return $name + $i;
            }, 'echo');
            $service->use([new ErrorToException(), 'handler']);
            $server = new MockServer('testErrorToException');
            $service->bind($server);
            $client = new Client(['mock://testErrorToException']);
            $proxy = $client->useService();
            $proxy->echo("Hello");
            $server->close();
        } finally {
            set_error_handler($error_handler);
        }
    }
    public function testForward() {
        $service = new Service();
        $service->addCallable(function (Context $context) {
            return $context->localAddress['address'];
        }, 'getAddress');
        $server = new MockServer('testAddress');
        $service->bind($server);

        $service2 = new Service();
        $server2 = new MockServer('testForward');
        $service2->use([new Forward(['mock://testAddress']), 'ioHandler']);
        $service2->bind($server2);

        $client = new Client(['mock://testForward']);
        $log = new Log();
        $client->use([$log, 'invokeHandler'], [$log, 'ioHandler']);
        $proxy = $client->useService();
        $result = $proxy->getAddress();

        $this->assertEquals($result, 'testAddress');
        $server2->close();
        $server->close();
    }
}