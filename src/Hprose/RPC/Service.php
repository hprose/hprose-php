<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Service.php                                              |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC;

class_alias('Hprose\\RPC\\Core\\Service', 'Hprose\\RPC\\Service');

if (!Service::isRegister('mock')) {
    Service::register('mock', 'Hprose\\RPC\\Mock\\MockHandler');
}
if (!Service::isRegister('http')) {
    Service::register('http', 'Hprose\\RPC\\Http\\HttpHandler');
}
