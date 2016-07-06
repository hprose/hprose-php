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
 * Hprose/InvokeSettings.php                              *
 *                                                        *
 * hprose InvokeSettings class for php 5.3+               *
 *                                                        *
 * LastModified: Jul 6, 2015                              *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose {
    class InvokeSettings {
        public $settings;
        public function __construct(array $settings = array()) {
            if ($settings !== null) {
                $this->settings = $settings;
            }
            else {
                $this->settings = array();
            }
        }
        public function __set($name, $value) {
            $this->settings[$name] = $value;
        }
        public function __get($name) {
            return $this->settings[$name];
        }
        public function __isset($name) {
            return isset($this->settings[$name]);
        }
        public function __unset($name) {
            unset($this->settings[$name]);
        }
    }
}