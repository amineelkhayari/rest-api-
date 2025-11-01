<?php
// If using Composer, prefer this:
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require $composerAutoload;
}

// Minimal PSR-4-ish autoloader (in case Composer is not used)
spl_autoload_register(function ($class) {
    $prefixes = [
        'App\\' => __DIR__ . '/../src/App/',
        'Core\\' => __DIR__ . '/../src/Core/'
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        if (strncmp($class, $prefix, strlen($prefix)) === 0) {
            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file))
                require $file;
        }
    }
});

[$container, $router] = require __DIR__ . '/../bootstrap.php';

$request = Core\Request::fromGlobals();
$response = new Core\Response();

// Global middleware pipeline

// $publicKey removed as requested
$issuer = 'https://localhost:5001';
$audience = 'accident';

$pipeline = new Core\MiddlewarePipeline([
    new App\Middleware\CorsMiddleware(),
    new App\Middleware\JsonBodyParser(),
    // Protect everything under /v1 with JWT and API key
    // You must provide the public key to JwtAuth for it to work
    // function ($req, $res, $next) use ($issuer, $audience) {
    //     if (strpos($req->getPath(), '/v1') === 0) {
    //         $jwtTest = new App\Middleware\JwtAuthMiddleware($issuer, $audience);
    //         return $jwtTest($req, $res, $next);
    //         //         //if ($result !== null && method_exists($result, 'send')) return $result;
    //         //         //$auth = new App\Middleware\ApiKeyAuth();
    //         //        // return $auth($req, $res, $next);
    //     }
    //     return $next($req, $res);
    // },
    // new App\Middleware\JwtAuthMiddleware($issuer, $audience)
]);

// Dispatch
$handler = function ($req, $res) use ($router) {
    return $router->dispatch($req, $res);
};
$final = $pipeline->handle($request, $response, $handler);
$final->send();
