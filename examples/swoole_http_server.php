<?php
    require_once('../Hprose.php');
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
    // function asyncHello($name, $callback) {
    //     swoole_timer_after(3000, function() use ($name, $callback) {
    //         $callback("Hello async $name!");
    //     });
    // }
    $server = new HproseSwooleServer("http://0.0.0.0:8000");
    $server->setErrorTypes(E_ALL);
    $server->setDebugEnabled();
    $server->addFunction('hello');
    $server->addFunctions(array('e', 'ee'));
    $server->addAsyncFunction('asyncHello');
    $server->start();
?>
