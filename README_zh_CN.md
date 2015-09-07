# Hprose for PHP

[![Join the chat at https://gitter.im/hprose/hprose-php](https://img.shields.io/badge/GITTER-join%20chat-green.svg)](https://gitter.im/hprose/hprose-php?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Packagist](https://img.shields.io/packagist/v/hprose/hprose.svg)](https://packagist.org/packages/hprose/hprose)
[![Packagist Download](https://img.shields.io/packagist/dm/hprose/hprose.svg)](https://packagist.org/packages/hprose/hprose)
[![License](https://img.shields.io/packagist/l/hprose/hprose.svg)](https://packagist.org/packages/hprose/hprose)

>---
- **[简介](#简介)**
- **[使用](#使用)**
    - **[服务器](#服务器)**
    - **[客户端](#客户端)**
    - **[异常处理](#异常处理)**

>---

## 简介

*Hprose* 是高性能远程对象服务引擎（High Performance Remote Object Service Engine）的缩写。

它是一个先进的轻量级的跨语言跨平台面向对象的高性能远程动态通讯中间件。它不仅简单易用，而且功能强大。你只需要稍许的时间去学习，就能用它轻松构建跨语言跨平台的分布式应用系统了。

*Hprose* 支持众多编程语言，例如：

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

通过 *Hprose*，你就可以在这些语言之间方便高效的实现互通了。

本项目是 Hprose 的 PHP 语言版本实现。

## 安装

### 通过下载源码
[下载地址](https://github.com/hprose/hprose-php/archive/master.zip)

### 通过composer
```javascript
{
    "require": {
        "hprose/hprose": "dev-master"
    }
}
```

## 使用

### 服务器

Hprose for PHP 使用起来很简单，你可以像这样来创建一个 Hprose 的 http 服务：

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

你也可以使用 HproseSwooleServer 来创建一个独立的 hprose 服务：

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

然后使用命令行来启动它：

`php server.php`

要使用 `HproseSwooleServer`, 你首先需要安装 [swoole](http://www.swoole.com/)。[swoole](https://github.com/swoole/swoole-src) 被支持的最低版本为 1.7.16.

`HproseSwooleServer` 不仅仅支持 http 服务器，还支持 tcp, unix 和 websocket 服务器。使用方法仅仅是创建时的 url 不同。例如：

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

另外，WebSocket 服务器同时也是 http 服务器，所以既可以用 WebSocket 客户端访问，也可以用 http 客户端访问。

### 客户端

然后你可以创建一个 Hprose 的 http 客户端来调用它了，就像这样：

```php
<?php
    require_once("Hprose.php");
    $client = new HproseHttpClient('http://127.0.0.1/server.php');
    echo $client->hello('World');
?>
```

Hprose 也提供了 HproseSwooleClient，它目前支持 http，tcp 和 unix 三种方式的调用。

```php
<?php
    require_once("Hprose.php");
    $client = new HproseSwooleClient('tcp://0.0.0.0:1234');
    echo $client->hello('World');
?>
```

新的版本现在也支持异步并发调用，例如：

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

异步调用的回调函数支持 0 - 3 个参数。它们分别表示：

|参数    |解释                                                       |
|-------:|:---------------------------------------------------------|
|结果    |就是服务器端的返回结果，如果没有结果则为 null。                  |
|调用参数|是一个包含了调用参数的数组，如果调用没有参数，则为 0 个元素的数组。 |
|错误    |一个 Exception 对象，如果没有错误则为 null。                   |

### 异常处理

如果服务器端发生错误，或者服务器端的函数或方法抛出异常，它将被发送给客户端，客户端会以异常的形式抛出，你可以使用 `try` 语句捕获它。异步调用时，服务器端返回的异常不会被抛出，而是传递给回调函数的第三个参数。
