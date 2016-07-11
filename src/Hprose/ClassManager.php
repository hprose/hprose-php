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
 * Hprose/ClassManager.php                                *
 *                                                        *
 * hprose class manager class for php 5.3+                *
 *                                                        *
 * LastModified: Jul 11, 2015                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose;

class ClassManager {
    private static $classCache1 = array();
    private static $classCache2 = array();
    public static function register($class, $alias) {
        self::$classCache1[$alias] = $class;
        self::$classCache2[$class] = $alias;
    }
    public static function getClassAlias($class) {
        if (isset(self::$classCache2[$class])) {
            return self::$classCache2[$class];
        }
        $alias = str_replace('\\', '_', $class);
        self::register($class, $alias);
        return $alias;
    }
    public static function getClass($alias) {
        if (isset(self::$classCache1[$alias])) {
            return self::$classCache1[$alias];
        }
        if (!class_exists($alias)) {
            $class = str_replace('_', '\\', $alias);
            if (class_exists($class)) {
                self::register($class, $alias);
                return $class;
            }
            return 'stdClass';
        }
        return $alias;
    }
}
