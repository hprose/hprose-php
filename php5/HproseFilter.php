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
 * HproseFilter.php                                       *
 *                                                        *
 * hprose filter interface for php5.                      *
 *                                                        *
 * LastModified: Mar 19, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

interface HproseFilter {
    function inputFilter($data, $context);
    function outputFilter($data, $context);
}