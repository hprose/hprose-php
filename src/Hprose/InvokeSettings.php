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
 * LastModified: Jul 27, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

use ArrayAccess;

class InvokeSettings implements ArrayAccess {
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
        return isset($this->settings[$name]) ? $this->settings[$name] : null;
    }
    public function __isset($name) {
        return isset($this->settings[$name]);
    }
    public function __unset($name) {
        unset($this->settings[$name]);
    }
    public function offsetSet($offset, $value) {
        $this->settings[$offset] = $value;
    }
    public function offsetGet($offset) {
        return isset($this->settings[$offset]) ? $this->settings[$offset] : null;
    }
    public function offsetExists($offset) {
        return isset($this->settings[$offset]);
    }
    public function offsetUnset($offset) {
        unset($this->settings[$offset]);
    }
}
