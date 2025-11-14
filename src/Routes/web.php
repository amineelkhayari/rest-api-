<?php

namespace Routes;
use Core\Router;  // ✅ Fix this line

final class Web
{
    public static function register(Router $router): void
    {
        $router->get('/', function ($req, $res) {
            json_encode($req);
             $filePath =  '../docs/index.php';
             if (!file_exists($filePath)) {
                return $res->json(['error' => 'Documentation index not found'], 404);
            }
            $html = file_get_contents($filePath);
            return $res->text($html, 200, 'text/html');
        });
    }
}
