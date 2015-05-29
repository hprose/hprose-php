<?php
include ('hprose/Hprose.php');
$t = time();
$client = new HproseHttpClient('http://127.0.0.1/HService.php');
$result = $client->run('module','method',['hello world'],[
		'appid' => 'appId',
		'sign' => sha1('appKey'.$t),
		'timestamp' => $t
	]);
var_dump($result);