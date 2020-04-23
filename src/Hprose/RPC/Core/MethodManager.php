<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| MethodManager.php                                        |
|                                                          |
| LastModified: Apr 23, 2020                               |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

use ReflectionMethod;

class MethodManager {
    private static $magicMethods = array(
        "__construct",
        "__destruct",
        "__call",
        "__callStatic",
        "__get",
        "__set",
        "__isset",
        "__unset",
        "__sleep",
        "__wakeup",
        "__toString",
        "__invoke",
        "__set_state",
        "__clone",
    );
    private $methods = [];
    private $names = [];
    public function getNames(): array{
        return $this->names;
    }
    public function get(string $fullname): Method {
        return $this->methods[strtolower($fullname)] ?? $this->methods['*'];
    }
    public function remove(string $fullname): void {
        usset($this->methods[strtolower($fullname)]);
        $this->names = array_values(array_diff($this->names, [$fullname]));
    }
    public function add(Method $method): void {
        $fullname = $method->fullname;
        $this->methods[strtolower($fullname)] = $method;
        if (!in_array($fullname, $this->names, true)) {
            $this->names[] = $fullname;
        }
    }
    public function addMissingMethod(callable $callable): void {
        $method = new Method($callable, '*');
        $method->missing = true;
        if (Utils::getNumberOfParameters($callable) === 3) {
            $method->passContext = true;
        }
        $this->add($method);
    }
    public function addCallable(callable $callable, ?string $fullname = null): void {
        $this->add(new Method($callable, $fullname));
    }
    public function addInstanceMethods(object $object, ?string $namespace = null): void {
        $methods = array_diff(get_class_methods($object), self::$magicMethods);
        foreach ($methods as $name) {
            $method = new ReflectionMethod($object, $name);
            if ($method->isPublic() &&
                !$method->isStatic() &&
                !$method->isConstructor() &&
                !$method->isDestructor() &&
                !$method->isAbstract()) {
                $this->addCallable([$object, $name], empty($namespace) ? $name : $namespace . '_' . $name);
            }
        }
    }
    public function addStaticMethods(string $class, ?string $namespace = null): void {
        $methods = array_diff(get_class_methods($class), self::$magicMethods);
        foreach ($methods as $name) {
            $method = new ReflectionMethod($class, $name);
            if ($method->isPublic() &&
                $method->isStatic() &&
                !$method->isAbstract()) {
                $this->addCallable([$class, $name], empty($namespace) ? $name : $namespace . '_' . $name);
            }
        }
    }
}