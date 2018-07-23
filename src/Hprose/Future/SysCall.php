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
 * Hprose/Future/SysCall.php                              *
 *                                                        *
\**********************************************************/

namespace Hprose\Future;

class SysCall {
    protected $callback = null;

    public function __construct(\Closure $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke($context)
    {
        return call_user_func($this->callback, $context);
    }
}
