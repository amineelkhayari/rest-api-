<?php
use App\Middleware\CorsMiddleware;
use App\Middleware\JsonBodyParser;
use Core\BootstrapClass;
use Core\MiddlewarePipeline;

// If using Composer, prefer this:
require_once __DIR__ . '/../vendor/autoload.php';


[$container, $router] = BootstrapClass::init();  // require_once __DIR__ . '/../bootstrap.php'

$request = Core\Request::fromGlobals();
$response = new Core\Response();

// Global middleware pipeline

// $publicKey removed as requested
$issuer = 'https://localhost:5001';
$audience = 'accident';

$pipeline = new MiddlewarePipeline([
    new CorsMiddleware(),
    new JsonBodyParser(),
]);

// Dispatch
$handler = function ($req, $res) use ($router) {
    return $router->dispatch($req, $res);
};
$final = $pipeline->handle($request, $response, $handler);
$final->send();
