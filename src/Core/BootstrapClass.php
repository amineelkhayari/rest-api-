<?php

namespace Core;

use App\Config\App;
use Core\AttributeRouteLoader;
use Core\Container;
use Core\ErrorHandler;
use Core\Router;
use Routes\Web;

final class BootstrapClass
{
    public static function init(): array
    {
        $config = App::get();

        // Simple error reporting based on config
        if ($config['debug']) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
        }

        // Create a super-light container
        $container = new Container();
        $container->set('config', $config);
        // make config globally available for simple loader/middleware wiring
        $GLOBALS['config'] = $config;

        // Register ErrorHandler
        ErrorHandler::register($config['debug']);

        $router = new Router();

        AttributeRouteLoader::load($router, null);

        // Optionally load legacy/manual routes
        Web::register($router);

        return [$container, $router];
    }
}
