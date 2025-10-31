<?php
namespace App\Middleware;

use Core\Request;
use Core\Response;

class JsonBodyParser
{
    public function __invoke(Request $req, Response $res, callable $next)
    {
        // Nothing to do; Request::fromGlobals already decodes JSON.
        return $next($req, $res);
    }
}
