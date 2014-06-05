<?php
/**********************************************************\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: http://www.hprose.com/                 |
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
    require_once('HproseSaeHttpClient.php');
}
elseif (function_exists('curl_init')) {
    require_once('HproseCurlHttpClient.php');
}
else {
    require_once('HproseFgcHttpClient.php');
}

?>