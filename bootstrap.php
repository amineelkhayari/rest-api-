<?php
use \Core\AttributeRouteLoader;
use \Core\DoctrineOrmFactory;

// Shared bootstrapping used by public/index.php (front controller)
$config = require __DIR__ . '/config/app.php';

// Simple error reporting based on config
if ($config['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

// Create a super-light container
require_once __DIR__ . '/src/Core/Container.php';
$container = new Core\Container();
$container->set('config', $config);
// make config globally available for simple loader/middleware wiring
$GLOBALS['config'] = $config;

// Register ErrorHandler
require_once __DIR__ . '/src/Core/ErrorHandler.php';
Core\ErrorHandler::register($config['debug']);

// Request/Response, Router and Middleware pipeline
require_once __DIR__ . '/src/Core/Request.php';
require_once __DIR__ . '/src/Core/Response.php';
require_once __DIR__ . '/src/Core/Router.php';
require_once __DIR__ . '/src/Core/MiddlewarePipeline.php';

// App controllers and middleware
require_once __DIR__ . '/src/Core/AttributeRouteLoader.php';
// controllers will be auto-discovered by AttributeRouteLoader when called without
// an explicit controller list
require_once __DIR__ . '/src/App/Middleware/CorsMiddleware.php';
require_once __DIR__ . '/src/App/Middleware/JsonBodyParser.php';
require_once __DIR__ . '/src/App/Middleware/ApiKeyAuth.php';


$router = new Core\Router();

// Load attribute-based routes (auto-discover controllers in src/App/Controllers)
//AttributeRouteLoader::load($router);
// $entityManager = DoctrineOrmFactory::createEntityManager();

// $GLOBALS['entityManager'] = $entityManager;
AttributeRouteLoader::load($router, null);



// Optionally load legacy/manual routes
require __DIR__ . '/routes/api.php';

return [$container, $router];
