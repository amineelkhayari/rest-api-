<?php
namespace App\Middleware;

use Core\Request;
use Core\Response;

class CorsMiddleware
{
    public function __invoke(Request $req, Response $res, callable $next)
    {
        // Preflight
        if ($req->getMethod() === 'OPTIONS') {
            return $res
                ->status(204)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Key')
                ->text('');
        }

        $resp = $next($req, $res);
        $resp->header('Access-Control-Allow-Origin', '*');
        return $resp;
    }
}
