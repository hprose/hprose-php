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
 * Hprose/ResultMode.php                                  *
 *                                                        *
 * hprose ResultMode enum for php 5.3+                    *
 *                                                        *
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

class ResultMode {
    const Normal = 0;
    const Serialized = 1;
    const Raw = 2;
    const RawWithEndTag = 3;
}
