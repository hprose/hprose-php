# Hprose for PHP

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

You can also use HproseSwooleHttpServer to create a standalone hprose server:

`server.php`
```php
<?php
    require_once("Hprose.php");

    function hello($name) {
        return 'Hello ' . $name;
    }

    $server = new HproseSwooleHttpServer('0.0.0.0', 8080);
    $server->addFunction('hello');
    $server->start();
```

then use command line to start it:

`php server.php`

To use HproseSwooleHttpServer, you need install [swoole](http://www.swoole.com/) first. The minimum version of [swoole](https://github.com/swoole/swoole-src) been supported is 1.7.11.

### Client

Then you can create a hprose client to invoke it like this:

```php
<?php
    require_once("Hprose.php");
    $client = new HproseHttpClient('http://127.0.0.1/server.php');
    echo $client->hello('World');
```

### Exception Handling

If an error occurred on the server, or your service function/method throw an exception. it will be sent to the client, and the client will throw it as an exception. You can use the try statement to catch it.
