<?php
namespace Core;

use ReflectionClass;
use ReflectionMethod;
use App\Helpers\Route;
use App\Helpers\ApiController;

class AttributeRouteLoader
{
    /**
     * Load routes from controller instances or auto-discover controllers
     * in src/App/Controllers when $controllers is empty.
     *
     * @param Router $router
     * @param array|null $controllers Array of controller instances or null to auto-discover
     */
    public static function load(Router $router, array $controllers = null): void
    {
        if ($controllers === null) {
            $controllers = self::discoverControllers();
        }

        foreach ($controllers as $controller) {
            $refClass = new ReflectionClass($controller);

            // skip classes not marked with #[ApiController] (optional)
            $classAttrs = $refClass->getAttributes(ApiController::class);
            if (count($classAttrs) === 0) {
                // if the class is in the Controllers namespace, still allow it
                // otherwise skip (conservative approach)
                if (strpos($refClass->getName(), 'App\\Controllers\\') === false) {
                    continue;
                }
            }

            foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes(Route::class);
                foreach ($attributes as $attr) {
                    /** @var Route $routeAttr */
                    $routeAttr = $attr->newInstance();
                    $router->add(
                        $routeAttr->method,
                        $routeAttr->path,
                        [$controller, $method->getName()]
                    );
                }
            }
        }
    }

    private static function discoverControllers(): array
    {
        $dir = __DIR__ . '/../App/Controllers';
        $controllers = [];
        if (!is_dir($dir)) return $controllers;

        $files = scandir($dir);
        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) continue;
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;
            $className = pathinfo($file, PATHINFO_FILENAME);
            $fqcn = 'App\\Controllers\\' . $className;
            if (class_exists($fqcn)) {
                $controllers[] = new $fqcn();
            } else {
                // attempt to require the file then instantiate
                require_once $dir . '/' . $file;
                if (class_exists($fqcn)) {
                    $controllers[] = new $fqcn();
                }
            }
        }

        return $controllers;
    }
}
