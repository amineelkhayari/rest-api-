<?php
// use App\Config\App;
// use Core\AttributeRouteLoader;
// use Core\DoctrineOrmFactory
// use Core\Router
// use Routes\Web

// // Shared bootstrapping used by public/index.php (front controller)
// $config = App::get();

// // Simple error reporting based on config
// if ($config['debug']) {
//     ini_set('display_errors', '1');
//     error_reporting(E_ALL);
// } else {
//     ini_set('display_errors', '0');
// }

// // Create a super-light container
// $container = new Core\Container();
// $container->set('config', $config);
// // make config globally available for simple loader/middleware wiring
// $GLOBALS['config'] = $config;

// // Register ErrorHandler
// Core\ErrorHandler::register($config['debug']);

// $router = new Router()

// AttributeRouteLoader::load($router, null)

// // Optionally load legacy/manual routes
// Web::register($router)

// return [$container, $router]

use App\Core\BootstrapClass;

[$container, $router] = BootstrapClass::init();
