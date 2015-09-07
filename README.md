# Hprose for PHP

[![Join the chat at https://gitter.im/hprose/hprose-php](https://img.shields.io/badge/GITTER-join%20chat-green.svg)](https://gitter.im/hprose/hprose-php?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Packagist](https://img.shields.io/packagist/v/hprose/hprose.svg)](https://packagist.org/packages/hprose/hprose)
[![Packagist Download](https://img.shields.io/packagist/dm/hprose/hprose.svg)](https://packagist.org/packages/hprose/hprose)
[![License](https://img.shields.io/packagist/l/hprose/hprose.svg)](https://packagist.org/packages/hprose/hprose)

>---
- **[Introduction](#introduction)**
- **[Usage](#usage)**
    - **[Server](#server)**
    - **[Client](#client)**
    - **[Exception Handling](#exception-handling)**

>---

## Introduction

*Hprose* is a High Performance Remote Object Service Engine.

It is a modern, lightweight, cross-language, cross-platform, object-oriented, high performance, remote dynamic communication middleware. It is not only easy to use, but powerful. You just need a little time to learn, then you can use it to easily construct cross language cross platform distributed application system.

*Hprose* supports many programming languages, for example:

* AAuto Quicker
* ActionScript
* ASP
* C++
* Dart
* Delphi/Free Pascal
* dotNET(C#, Visual Basic...)
* Golang
* Java
* JavaScript
* Node.js
* Objective-C
* Perl
* PHP
* Python
* Ruby
* ...

Through *Hprose*, You can conveniently and efficiently intercommunicate between those programming languages.

This project is the implementation of Hprose for PHP.

## Installation

### Download Source Code
[Download Link](https://github.com/hprose/hprose-php/archive/master.zip)

### install by `composer`
```javascript
{
    "require": {
        "hprose/hprose": "dev-master"
    }
}
```

## Usage

### Server

Hprose for PHP is very easy to use. You can create a hprose server like this:

```php
<?php
    require_once('Hprose.php');

    function hello($name) {
        return 'Hello ' . $name;
    }

    $server = new HproseHttpServer();
    $server->addFunction('hello');
    $server->start();

```

You can also use `HproseSwooleServer` to create a standalone hprose server:

`server.php`
```php
<?php
    require_once("Hprose.php");

    function hello($name) {
        return 'Hello ' . $name;
    }

    $server = new HproseSwooleServer('http://0.0.0.0:8080/');
    $server->addFunction('hello');
    $server->start();
```

then use command line to start it:

`php server.php`

To use `HproseSwooleServer`, you need install [swoole](http://www.swoole.com/) first. The minimum version of [swoole](https://github.com/swoole/swoole-src) been supported is 1.7.16.

`HproseSwooleServer` not only support creating http server，but also support create tcp, unix and websocket server. For examples:

`tcp_server.php`
```php
<?php
    require_once("Hprose.php");

    function hello($name) {
        return 'Hello ' . $name;
    }

    $server = new HproseSwooleServer('tcp://0.0.0.0:1234');
    $server->addFunction('hello');
    $server->start();
```

`unix_server.php`
```php
<?php
    require_once("Hprose.php");

    function hello($name) {
        return 'Hello ' . $name;
    }

    $server = new HproseSwooleServer('unix:/tmp/my.sock');
    $server->addFunction('hello');
    $server->start();
```

`websocket_server.php`
```php
<?php
    require_once("Hprose.php");

    function hello($name) {
        return 'Hello ' . $name;
    }

    $server = new HproseSwooleServer('ws://0.0.0.0:8000/');
    $server->addFunction('hello');
    $server->start();
```

The websocket server is also a http server.

### Client

Then you can create a hprose client to invoke it like this:

```php
<?php
    require_once("Hprose.php");
    $client = new HproseHttpClient('http://127.0.0.1/server.php');
    echo $client->hello('World');
```

Hprose also suplied `HproseSwooleClient`，it supports http，tcp and unix. For example:

```php
<?php
    require_once("Hprose.php");
    $client = new HproseSwooleClient('tcp://0.0.0.0:1234');
    echo $client->hello('World');
?>
```

It also support asynchronous concurrency invoke. For example:

```php
<?php
    require_once("Hprose.php");
    $client = new HproseSwooleClient('tcp://0.0.0.0:1234');
    $client->hello('World', function() {
        echo "ok\r\n";
    });
    $client->hello('World 1', function($result) {
        echo $result . "\r\n";
    });
    $client->hello('World 2', function($result, $args) {
        echo $result . "\r\n";
    });
    $client->hello('World 3', function($result, $args, $error) {
        echo $result . "\r\n";
    });
?>
```

the callback function of asynchronous concurrency invoking supports 0 - 3 parameters:

|params   |comments                                                           |
|--------:|:------------------------------------------------------------------|
|result   |The result is the server returned, if no result, its value is null.|
|arguments|It is an array of arguments. if no argument, it is an empty array. |
|error    |It is an object of Exception, if no error, its value is null.      |

### Exception Handling

If an error occurred on the server, or your service function/method throw an exception. it will be sent to the client, and the client will throw it as an exception. You can use the try statement to catch it.

No exception throwed on asynchonous invoking. The exception object will be passed to the callback function as the third argument.
