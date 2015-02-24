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
 * LastModified: Feb 24, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (class_exists('SaeFetchurl', false)) {
    include('HproseSaeHttpClient.php');
}
elseif (function_exists('curl_init')) {
    include('HproseCurlHttpClient.php');
}
else {
    include('HproseFgcHttpClient.php');
}

?>
