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
 * HproseIO.php                                           *
 *                                                        *
 * hprose io library for php5.                            *
 *                                                        *
 * LastModified: Jul 12, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (!extension_loaded('hprose')) {

require_once('HproseTags.php');
require_once('HproseClassManager.php');
require_once('HproseReader.php');
require_once('HproseWriter.php');
require_once('HproseFormatter.php');

} // endif (!extension_loaded('hprose'))
?>
