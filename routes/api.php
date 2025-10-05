<?php
// use Core\Router;
// use App\Controllers\HomeController;
// use App\Controllers\UserController;

// /** @var Router $router */
// $router->get('/', function ($req, $res) {
//     return $res->json(['name' => 'PHP API Starter', 'docs' => '/docs']);
// });

// $router->get('/health', [new HomeController(), 'health']);

// // Versioned API (guarded by ApiKeyAuth in index.php)
// $router->get('/v1/users', [new UserController(), 'index']);
// $router->get('/v1/users/{id}', [new UserController(), 'show']);
// $router->post('/v1/users', [new UserController(), 'store']);

// // Serve OpenAPI and Swagger UI (static)
// $router->get('/openapi.yaml', function ($req, $res) {
//     $yaml = file_get_contents(__DIR__ . '/../openapi.yaml');
//     return $res->text($yaml, 200, 'application/yaml');
// });

$router->get('/', function ($req, $res) {
    $html = file_get_contents(__DIR__ . '/../docs/index.php');
    return $res->text($html, 200, 'text/html');
});