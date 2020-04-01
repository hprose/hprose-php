<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Client.php                                               |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC;

class_alias('Hprose\\RPC\\Core\\Client', 'Hprose\\RPC\\Client');

if (!Client::isRegister('mock')) {
    Client::register('mock', 'Hprose\\RPC\\Mock\\MockTransport');
}
if (!Client::isRegister('http')) {
    Client::register('http', 'Hprose\\RPC\\Http\\HttpTransport');
}
