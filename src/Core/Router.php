<?php
namespace Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $method = strtoupper($method);
        $regex = $this->convertToRegex($pattern);
        $this->routes[$method][] = ['pattern' => $pattern, 'regex' => $regex, 'handler' => $handler];
    }

    public function get(string $pattern, callable $handler): void { $this->add('GET', $pattern, $handler); }
    public function post(string $pattern, callable $handler): void { $this->add('POST', $pattern, $handler); }
    public function put(string $pattern, callable $handler): void { $this->add('PUT', $pattern, $handler); }
    public function delete(string $pattern, callable $handler): void { $this->add('DELETE', $pattern, $handler); }

    public function dispatch(Request $req, Response $res): Response
    {
        $method = $req->getMethod();
        $path = $req->getPath();

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($matches as $k => $v) if (!is_int($k)) $params[$k] = $v;
                return call_user_func($route['handler'], $req, $res, $params);
            }
        }

        return $res->json(['error' => 'Not Found', 'path' => $path], 404);
    }

    private function convertToRegex(string $pattern): string
    {
        // Convert patterns like /v1/users/{id} to named capture regex
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        return '#^' . rtrim($regex, '/') . '/?$#';
    }
}