<?php
namespace Core;

use ReflectionClass;
use ReflectionMethod;
use App\Helpers\Route;
use App\Helpers\ApiController;
use App\Helpers\Authorize;
use App\Helpers\AllowAnonymous;
use App\Middleware\JwtAuthMiddleware;

class AttributeRouteLoader
{
    public static function load(Router $router, array $controllers = null): void
    {
        $controllers ??= self::discoverControllers();

        foreach ($controllers as $controller) {
            $refClass = new ReflectionClass($controller);

            // skip classes not marked #[ApiController], unless in Controllers namespace
            if (!$refClass->getAttributes(ApiController::class)
                && !str_starts_with($refClass->getName(), 'App\\Controllers\\')) {
                continue;
            }

            // Pre-fetch class-level auth attributes once
            $classAuthorize = self::getAttributeInstance($refClass, Authorize::class);
            $classAllowsAnon = (bool) $refClass->getAttributes(AllowAnonymous::class);
            $classRequiresAuth = (bool) $classAuthorize;

            foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $routeAttributes = $method->getAttributes(Route::class);
                if (!$routeAttributes) continue;

                $methodAuthorize = self::getAttributeInstance($method, Authorize::class);
                $methodAllowsAnon = (bool) $method->getAttributes(AllowAnonymous::class);
                $methodRequiresAuth = (bool) $methodAuthorize;

                // Determine final auth requirements (method overrides class)
                $requiresAuth = $methodRequiresAuth
                    || (!$methodAllowsAnon && $classRequiresAuth && !$classAllowsAnon);

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
                if ($policy && !$jwt->hasAnyClaims($claims, [$policy], "scope")) {
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

    /**
     * Discover controllers automatically in App\Controllers directory.
     */
    private static function discoverControllers(): array
    {
        $dir = __DIR__ . '/../App/Controllers';
        if (!is_dir($dir)) return [];

        $controllers = [];
        foreach (scandir($dir) as $file) {
            if ($file[0] === '.' || pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;

            $fqcn = 'App\\Controllers\\' . pathinfo($file, PATHINFO_FILENAME);
            $path = $dir . '/' . $file;

            if (!class_exists($fqcn)) require_once $path;
            if (class_exists($fqcn)) $controllers[] = new $fqcn();
        }

        return $controllers;
    }
}

// namespace Core;

// use ReflectionClass;
// use ReflectionMethod;
// use App\Helpers\Route;
// use App\Helpers\ApiController;
// use App\Helpers\Authorize;
// use App\Helpers\AllowAnonymous;
// use App\Middleware\JwtAuthMiddleware;

// class AttributeRouteLoader
// {
//     /**
//      * Load routes from controller instances or auto-discover controllers
//      * in src/App/Controllers when $controllers is empty.
//      *
//      * @param Router $router
//      * @param array|null $controllers Array of controller instances or null to auto-discover
//      */
//     public static function load(Router $router, array $controllers = null): void
//     {
//         if ($controllers === null) {
//             $controllers = self::discoverControllers();
//         }

//         foreach ($controllers as $controller) {
//             $refClass = new ReflectionClass($controller);

//             // skip classes not marked with #[ApiController] (optional)
//             $classAttrs = $refClass->getAttributes(ApiController::class);
//             if (count($classAttrs) === 0) {
//                 // if the class is in the Controllers namespace, still allow it
//                 // otherwise skip (conservative approach)
//                 if (strpos($refClass->getName(), 'App\\Controllers\\') === false) {
//                     continue;
//                 }
//             }

//             foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
//                 $attributes = $method->getAttributes(Route::class);

//                 foreach ($attributes as $attr) {
//                     /** @var Route $routeAttr */
//                     $routeAttr = $attr->newInstance();
//                     // Determine auth requirements: class-level and method-level
//                     $methodAuthAttrs = $method->getAttributes(Authorize::class);
//                     $classAuthAttrs  = $refClass->getAttributes(Authorize::class);

//                     // prefer method-level roles/policy then class-level
//                     $requiredRoles = [];
//                     $policyMethod = null;
//                     if (count($methodAuthAttrs) > 0) {
//                         $ma = $methodAuthAttrs[0]->newInstance();
//                         $requiredRoles = $ma->roles ?? [];
//                         $policyMethod = $ma->policy ?? null;
//                     } elseif (count($classAuthAttrs) > 0) {
//                         $ca = $classAuthAttrs[0]->newInstance();
//                         $requiredRoles = $ca->roles ?? [];
//                         $policyMethod = $ca->policy ?? null;
//                     }

//                     $methodAllowsAnonymous = count($method->getAttributes(AllowAnonymous::class)) > 0;
//                     $methodRequiresAuth = count($method->getAttributes(Authorize::class)) > 0;
//                     $classAllowsAnonymous = count($refClass->getAttributes(AllowAnonymous::class)) > 0;
//                     $classRequiresAuth = count($refClass->getAttributes(Authorize::class)) > 0;


//                     $requiresAuth = false;
//                     // Method-level attributes override class-level
//                     if ($methodAllowsAnonymous) {
//                         $requiresAuth = false;
//                     } elseif ($methodRequiresAuth) {
//                         $requiresAuth = true;
//                     } else {
//                         // fallback to class-level
//                         if ($classAllowsAnonymous) $requiresAuth = false;
//                         elseif ($classRequiresAuth) $requiresAuth = true;
//                         else $requiresAuth = false; // default: open
//                     }
//                     $handler = [$controller, $method->getName()];
//                     if ($requiresAuth) {
//                         // wrap handler to run JwtAuthMiddleware first and enforce roles/policy
//                         $issuer = rtrim(($GLOBALS['config']['oAuth']['issuer'] ?? ''), '/');
//                         $aud = $GLOBALS['config']['oAuth']['audience'] ?? null;
//                         $jwtMiddleware = new JwtAuthMiddleware($issuer, $aud);

//                         $wrapped = function ($req, $res, $params) use ($jwtMiddleware, $handler, $requiredRoles, $policyMethod, $controller, $method) {
//                             // JwtAuthMiddleware follows the signature ($req,$res,$next)
//                             $next = function ($r, $s) use ($handler, $params, $requiredRoles, $policyMethod, $controller, $method, $res, $jwtMiddleware) {
//                                 // After auth, $r->user contains claims
//                                 $claims = $r->user ?? [];
//                                 // RBAC: roles check
//                                 if (!empty($requiredRoles) || $policyMethod ) {
//                                     if (!$jwtMiddleware->hasAnyRole($claims, $requiredRoles) ) {
//                                         return $s->json(['error' => 'Forbidden', 'required_roles' => $requiredRoles], 403);
//                                     }
//                                 }

//                                 // ABAC: if a policy method is configured on the controller, call it
//                                 if($policyMethod){
//                                     if (!$jwtMiddleware->hasAnyClaims($claims, [$policyMethod],"scope")) {
//                                         return $s->json(['error' => 'Forbidden by policy', 'policy' => $policyMethod], 403);
//                                     }
//                                 }
                                
//                                 return call_user_func($handler, $r, $s, $params);
//                             };

//                             return $jwtMiddleware($req, $res, $next);
//                         };

//                         $router->add(
//                             $routeAttr->method,
//                             $routeAttr->path,
//                             $wrapped
//                         );
//                     } else {
//                         $router->add(
//                             $routeAttr->method,
//                             $routeAttr->path,
//                             $handler
//                         );
//                     }
//                 }
//             }
//         }
//     }

//     private static function discoverControllers(): array
//     {
//         $dir = __DIR__ . '/../App/Controllers';
//         $controllers = [];
//         if (!is_dir($dir)) return $controllers;

//         $files = scandir($dir);
//         foreach ($files as $file) {
//             if (in_array($file, ['.', '..'])) continue;
//             if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;
//             $className = pathinfo($file, PATHINFO_FILENAME);
//             $fqcn = 'App\\Controllers\\' . $className;
//             if (class_exists($fqcn)) {
//                 $controllers[] = new $fqcn();
//             } else {
//                 // attempt to require the file then instantiate
//                 require_once $dir . '/' . $file;
//                 if (class_exists($fqcn)) {
//                     $controllers[] = new $fqcn();
//                 }
//             }
//         }

//         return $controllers;
//     }
// }
