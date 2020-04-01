<?php
/*--------------------------------------------------------*\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: https://hprose.com                     |
|                                                          |
| Service.php                                              |
|                                                          |
| LastModified: Apr 1, 2020                                |
| Author: Ma Bingyao <andot@hprose.com>                    |
|                                                          |
\*________________________________________________________*/

namespace Hprose\RPC\Core;

use InvalidArgumentException;
use Throwable;

class Service {
    private static $handlerClasses = [];
    private static $serverTypes = [];
    static function register(string $name, string $handlerClass): void {
        $types = $handlerClass::$serverTypes;
        foreach ($types as $type) {
            if (isset(static::$serverTypes[$type])) {
                static::$serverTypes[$type][] = $name;
            } else {
                static::$serverTypes[$type] = [$name];
            }
        }
        static::$handlerClasses[$name] = $handlerClass;
    }
    static function isRegister(string $name): bool {
        return isset(static::$handlerClasses[$name]);
    }
    /** @var ServiceCodec $codec */
    public $codec;
    public $maxRequestLength = 0x7FFFFFFFF;
    public $options = [];
    private $invokeManager;
    private $ioManager;
    use PluginTrait;
    private $methodManager;
    private $handlers = [];
    public function __get(string $name): Handler {
        return $this->handlers[$name];
    }
    public function __set(string $name, Handler $handler): void {
        $this->handlers[$name] = $handler;
    }
    public function getNames(): array{
        return $this->methodManager->getNames();
    }
    public function __construct() {
        $this->codec = DefaultServiceCodec::getInstance();
        $this->invokeManager = new InvokeManager([$this, 'execute']);
        $this->ioManager = new IOManager([$this, 'process']);
        foreach (static::$handlerClasses as $name => $handlerClass) {
            $this->handlers[$name] = new $handlerClass($this);
        }
        $this->methodManager = new MethodManager();
        $this->addCallable([$this->methodManager, 'getNames'], '~');
    }
    public function createContext(): ServiceContext {
        return new ServiceContext($this);
    }
    public function bind($server, ?string $name = null): void {
        $type = get_class($server);
        if (isset(self::$serverTypes[$type])) {
            $names = self::$serverTypes[$type];
            foreach ($names as $n) {
                if (empty($name) || $name === $n) {
                    $this->handlers[$n]->bind($server);
                }
            }
        } else {
            throw new InvalidArgumentException('This type server is not supported.');
        }
    }
    public function handle(string $request, Context $context): string {
        return call_user_func($this->ioManager->handler, $request, $context);
    }
    public function process(string $request, Context $context): string {
        $result = null;
        try {
            [$name, $args] = $this->codec->decode($request, $context);
            $result = call_user_func_array($this->invokeManager->handler, [$name, &$args, $context]);
        } catch (Throwable $e) {
            $result = $e;
        }
        return $this->codec->encode($result, $context);
    }
    public function execute(string $fullname, array &$args, Context $context) {
        $method = $context->method;
        if ($method->missing) {
            if ($method->passContext) {
                return call_user_func_array($method->callable, [$fullname, &$args, $context]);
            }
            return call_user_func_array($method->callable, [$fullname, &$args]);
        }
        if ($method->passContext) {
            $args[] = $context;
        }
        return call_user_func_array($method->callable, $args);
    }
    public function get(string $fullname): Method {
        return $this->methodManager->get($fullname);
    }
    public function add(Method $method): self {
        $this->methodManager->add($method);
        return $this;
    }
    public function remove(Method $method): self {
        $this->methodManager->remove($method);
        return $this;
    }
    public function addMissingMethod(callable $callable): self {
        $this->methodManager->addMissingMethod($callable);
        return $this;
    }
    public function addCallable(callable $callable, ?string $fullname = null): self {
        $this->methodManager->addCallable($callable, $fullname);
        return $this;
    }
    public function addInstanceMethods(object $object, ?string $namespace = null): self {
        $this->methodManager->addInstanceMethods($object, $namespace);
        return $this;
    }
    public function addStaticMethods(string $class, ?string $namespace = null): self {
        $this->methodManager->addStaticMethods($class, $namespace);
        return $this;
    }
}