<?php
namespace App\Middleware;

use Core\Request;
use Core\Response;
use App\Config\App;

class ApiKeyAuth
{
    public function __invoke(Request $req, Response $res, callable $next)
    {
        $config = App::get();
        // require __DIR__ . '/../../../config/app.php'
        $provided = $req->getHeader('X-API-Key') ?? ($_GET['api_key'] ?? null);
        if ($provided !== $config['api_key']) {
            return $res->json(['error' => 'Unauthorized'], 401);
        }
        return $next($req, $res);
    }
}
