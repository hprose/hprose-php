<?php
    require_once('../src/Hprose.php');
    function hello($name) {
        echo "Hello $name!";
        return "Hello $name!";
    }
    function e() {
        throw new Exception("I am Exception");
    }
    function ee() {
        require("andot");
    }
    function asyncHello($name, $callback) {
        sleep(3);
        $callback("Hello async $name!");
    }
    function dnslookup($domain_name, $callback) {
        swoole_async_dns_lookup($domain_name, function($host, $ip) use ($callback) {
            $callback($ip);
        });
    }
    $server = new HproseSwooleServer("http://0.0.0.0:8000");
    $server->setErrorTypes(E_ALL);
    $server->setDebugEnabled();
    $server->addFunction('hello');
    $server->addFunctions(array('e', 'ee'));
    $server->addAsyncFunctions(array('asyncHello', 'dnslookup'));
    $server->start();
?>
