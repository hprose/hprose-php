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
 * HproseResultMode.php                                   *
 *                                                        *
 * hprose ResultMode for php5.                            *
 *                                                        *
 * LastModified: Jan 2, 2014                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

class HproseResultMode {
    const Normal = 0;
    const Serialized = 1;
    const Raw = 2;
    const RawWithEndTag = 3;
}