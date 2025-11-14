<?php
namespace Core;

use App\Helpers\Attributes\AllowAnonymous;
use App\Helpers\Attributes\ApiController;
use App\Helpers\Attributes\Authorize;
use App\Helpers\Attributes\Route;
use App\Middleware\JwtAuthMiddleware;
use ReflectionClass;
use ReflectionMethod;

class AttributeRouteLoader
{
    public static function load(Router $router, array $controllers = null): void
    {
        $controllers ??= self::discoverControllers();

        foreach ($controllers as $controller) {
            $refClass = new ReflectionClass($controller);

            // skip classes not marked #[ApiController], unless in Controllers namespace
            if (!$refClass->getAttributes(ApiController::class) &&
                    !str_starts_with($refClass->getName(), 'App\\Controllers\\')) {
                continue;
            }

            // Pre-fetch class-level auth attributes once
            $classAuthorize = self::getAttributeInstance($refClass, Authorize::class);
            $classAllowsAnon = (bool) $refClass->getAttributes(AllowAnonymous::class);
            $classRequiresAuth = (bool) $classAuthorize;

            foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $routeAttributes = $method->getAttributes(Route::class);
                if (!$routeAttributes) {
                    continue;
                }

                $methodAuthorize = self::getAttributeInstance($method, Authorize::class);
                $methodAllowsAnon = (bool) $method->getAttributes(AllowAnonymous::class);
                $methodRequiresAuth = (bool) $methodAuthorize;

                // Determine final auth requirements (method overrides class)
                $requiresAuth = $methodRequiresAuth ||
                    (!$methodAllowsAnon && $classRequiresAuth && !$classAllowsAnon);

                $roles = $methodAuthorize->roles
                    ?? $classAuthorize->roles
                    ?? [];
                $policy = $methodAuthorize->policy
                    ?? $classAuthorize->policy
                    ?? null;

                $handler = [$controller, $method->getName()];

                foreach ($routeAttributes as $attr) {
                    $route = $attr->newInstance();

                    if ($requiresAuth) {
                        $router->add(
                            $route->method,
                            $route->path,
                            self::wrapWithJwtMiddleware(
                                $handler,
                                $roles,
                                $policy
                            )
                        );
                    } else {
                        $router->add($route->method, $route->path, $handler);
                    }
                }
            }
        }
    }

    /**
     * Create a wrapped handler that applies JWT authentication and role/policy checks.
     */
    private static function wrapWithJwtMiddleware($handler, array $roles, ?string $policy): callable
    {
        static $jwt = null;

        if ($jwt === null) {
            $issuer = rtrim(($GLOBALS['config']['oAuth']['issuer'] ?? ''), '/');
            $aud = $GLOBALS['config']['oAuth']['audience'] ?? null;
            $jwt = new JwtAuthMiddleware($issuer, $aud);
        }

        return function ($req, $res, $params) use ($jwt, $handler, $roles, $policy) {
            $next = function ($r, $s) use ($handler, $params, $jwt, $roles, $policy) {
                $claims = $r->user ?? [];

                // RBAC
                if ($roles && !$jwt->hasAnyRole($claims, $roles)) {
                    return $s->json(['error' => 'Forbidden', 'required_roles' => $roles], 403);
                }

                // ABAC (policy check)
                if ($policy && !$jwt->hasAnyClaims($claims, [$policy], 'scope')) {
                    return $s->json(['error' => 'Forbidden by policy', 'policy' => $policy], 403);
                }

                return call_user_func($handler, $r, $s, $params);
            };

            return $jwt($req, $res, $next);
        };
    }

    /**
     * Returns the first instance of an attribute or null.
     */
    private static function getAttributeInstance($ref, string $attrClass)
    {
        $attrs = $ref->getAttributes($attrClass);
        return $attrs ? $attrs[0]->newInstance() : null;
    }

    private static function discoverControllers(array $services = []): array
    {
        $dir = __DIR__ . '/../App/Controllers';
        if (!is_dir($dir)) {
            return [];
        }

        $controllers = [];

        foreach (scandir($dir) as $file) {
            if ($file[0] === '.' || pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            $fqcn = 'App\\Controllers\\' . pathinfo($file, PATHINFO_FILENAME);
            if (!class_exists($fqcn)) {
                require_once $dir . '/' . $file;
            }
            if (!class_exists($fqcn)) {
                continue;
            }

            $refClass = new ReflectionClass($fqcn);
            $constructor = $refClass->getConstructor();

            // No constructor? instantiate directly
            if (!$constructor || $constructor->getNumberOfParameters() === 0) {
                $controllers[] = $refClass->newInstance();
                continue;
            }

            // Try to resolve constructor parameters from $services
            $args = [];
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType()?->getName();

                if ($type && isset($services[$type])) {
                    $args[] = $services[$type];
                } elseif ($param->isOptional()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    // Pass null if unresolved (so no crash)
                    $args[] = null;
                }
            }

            $controllers[] = $refClass->newInstanceArgs($args);
        }

        return $controllers;
    }
}
