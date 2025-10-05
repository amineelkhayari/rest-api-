<?php
namespace Core;

use App\Helpers\Route;
use ReflectionClass;
use ReflectionMethod;

class RouteLoader
{
    public function __construct(private Router $router) {}

    public function registerController(string $controllerClass): void
    {
        $rc = new ReflectionClass($controllerClass);


        // Class-level route prefix
        $basePath = '';
        foreach ($rc->getAttributes(Route::class) as $attr) {
            $basePath = $attr->newInstance()->path;
        }
        echo "<hello>";

        // Scan methods
        foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(Route::class) as $attr) {
                $route = $attr->newInstance();
                $path  = rtrim($basePath, '/') . '/' . ltrim($route->path, '/');

                $this->router->add(
                    $route->method,
                    $path,
                    fn(Request $req, Response $res, array $params = []) =>
                        $method->invoke(new $controllerClass(), $req, $res, $params)
                );
            }
        }
    }
}
