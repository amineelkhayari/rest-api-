<?php
namespace Core;

class MiddlewarePipeline
{
    private array $stack;

    public function __construct(array $stack = [])
    {
        $this->stack = $stack;
    }

    public function handle(Request $req, Response $res, callable $core)
    {
        $runner = array_reduce(
            array_reverse($this->stack),
            function ($next, $middleware) {
                return function ($req, $res) use ($middleware, $next) {
                    if (is_callable($middleware)) {
                        return $middleware($req, $res, $next);
                    }
                    // object with __invoke
                    return $middleware($req, $res, $next);
                };
            },
            $core
        );

        return $runner($req, $res);
    }
}
