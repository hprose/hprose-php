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
	$server = new HproseSwooleServer("ws://0.0.0.0:8080");
	$server->setErrorTypes(E_ALL);
	$server->setDebugEnabled();
	$server->addFunction('hello');
	$server->addFunctions(array('e', 'ee'));
	$server->start();
?>
