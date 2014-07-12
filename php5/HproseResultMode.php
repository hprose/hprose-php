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
 * HproseResultMode.php                                   *
 *                                                        *
 * hprose ResultMode for php5.                            *
 *                                                        *
 * LastModified: Jul 12, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (!extension_loaded('hprose')) {

class HproseResultMode {
    const Normal = 0;
    const Serialized = 1;
    const Raw = 2;
    const RawWithEndTag = 3;
}

} // endif (!extension_loaded('hprose'))
?>