<?php
namespace App\Middleware;

use Core\Request;
use Core\Response;

class CorsMiddleware
{
    private array $allowedOrigins = [
        'http://localhost:8080',
        'https://chatgpt.com'
    ];

    public function __invoke(Request $req, Response $res, callable $next)
    {
        $origin = $req->getHeader('Origin');
        $method = $req->getMethod();
        $allowed = $origin === null || in_array($origin, $this->allowedOrigins, true);

        $response = null;

        // Handle preflight
        if ($method === 'OPTIONS') {
            if (!$allowed) {
                $response = $res->status(403)->text('CORS Forbidden');
            } else {
                $response = $res
                    ->header('Access-Control-Allow-Origin', $origin ?? '')
                    ->header('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
                    ->header('Access-Control-Allow-Credentials', 'true')
                    ->text('')
                    ->status(204);
            }
        } elseif (!$allowed) {
            // Block disallowed origins
            $response = $res->status(403)->text('CORS Forbidden');
        } else {
            // Allowed origin → process next middleware
            $response = $next($req, $res);
            if ($origin !== null) {
                $response
                    ->header('Access-Control-Allow-Origin', $origin)
                    ->header('Access-Control-Allow-Credentials', 'true');
            }
        }

        return $response;
    }
}
