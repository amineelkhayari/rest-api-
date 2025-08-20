<?php
namespace Core;

use ReflectionClass;
use ReflectionMethod;
use App\Route;

class AttributeRouteLoader
{
    public static function load(Router $router, array $controllers): void
    {
        foreach ($controllers as $controller) {
            $refClass = new ReflectionClass($controller);
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
}
