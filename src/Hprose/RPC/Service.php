<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Hprose/RPC/Service.php                                   |
|                                                          |
| Hprose Service for PHP 7.1+                              |
|                                                          |
| LastModified: Feb 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC;

class_alias('Hprose\\RPC\\Core\\Service', 'Hprose\\RPC\\Service');

if (!Service::isRegister('mock')) {
    Service::register('mock', 'Hprose\\RPC\\Mock\\MockHandler');
}
