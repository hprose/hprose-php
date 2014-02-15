# Hprose for PHP

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

## 使用

### 服务器

Hprose for PHP 使用起来很简单，你可以像这样来创建一个 Hprose 的 http 服务:

```php
<?php
    require_once('php5/HproseHttpServer.php');

    function hello($name) {
        return 'Hello ' . $name;
    }

    $server = new HproseHttpServer();
    $server->addFunction('hello');
    $server->start();
?>

```

### 客户端

然后你可以创建一个 Hprose 的 http 客户端来调用它了，就像这样：

```php
<?php
    require_once("php5/HproseHttpClient.php");
    $client = new HproseHttpClient('http://127.0.0.1/server.php');
    echo $client->hello('World');
?>
```

### 异常处理

如果服务器端发生错误，或者服务器端的函数或方法抛出异常，它将被发送给客户端，客户端会以异常的形式抛出，你可以使用 `try` 语句捕获它。
