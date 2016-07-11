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
 * Throwable.php                                          *
 *                                                        *
 * Throwable for php 7-                                   *
 *                                                        *
 * LastModified: Jul 11, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (!interface_exists("Throwable")) {
    interface Throwable {
        public function getMessage();
        public function getCode();
        public function getFile();
        public function getLine();
        public function getTrace();
        public function getTraceAsString();
        public function getPrevious();
        public function __toString();
    }
}