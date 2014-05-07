<?php
/**********************************************************\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: http://www.hprose.com/                 |
|                   http://www.hprose.net/                 |
|                   http://www.hprose.org/                 |
|                                                          |
\**********************************************************/

/**********************************************************\
 *                                                        *
 * HproseHttpClient.php                                   *
 *                                                        *
 * hprose http client library for php5.                   *
 *                                                        *
 * LastModified: Jan 2, 2014                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (class_exists('SaeFetchurl', false)) {
    require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'HproseSaeHttpClient.php');
}
elseif (function_exists('curl_init')) {
    require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'HproseCurlHttpClient.php');
}
else {
    require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'HproseFgcHttpClient.php');
}