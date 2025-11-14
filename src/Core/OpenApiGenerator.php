<?php
namespace Core;

use App\Helpers\Route;
use ReflectionClass;
use ReflectionMethod;

class OpenApiGenerator
{
    public static function generateYaml(array $controllers, string $outputFile): void
    {
        $openapi = self::buildSpec($controllers);
        file_put_contents($outputFile, self::yamlDump($openapi));
    }

    public static function generateJson(array $controllers, string $outputFile): void
    {
        $openapi = self::buildSpec($controllers);
        file_put_contents($outputFile, json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private static function buildSpec(array $controllers): array
    {
        $paths = [];
        foreach ($controllers as $controller) {
            $refClass = new ReflectionClass($controller);
            foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes(Route::class);
                foreach ($attributes as $attr) {
                    /** @var Route $routeAttr */
                    $routeAttr = $attr->newInstance();
                    $path = $routeAttr->path;
                    $httpMethod = strtolower($routeAttr->method);
                    $paths[$path][$httpMethod] = [
                        'summary' => $method->getName(),
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];
                }
            }
        }

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'PHP API Starter',
                'version' => '1.0.0',
                'description' => 'Auto-generated OpenAPI spec.'
            ],
            'servers' => [['url' => '/pub-api/public']],
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key'
                    ],
                    'OAuth2accident' => [
                        'type' => 'oauth2',
                        'flows' => [
                            'authorizationCode' => [
                                'authorizationUrl' => 'https://localhost:5001/connect/authorize',
                                'tokenUrl' => 'https://localhost:5001/connect/token',
                                'scopes' => [
                                    'accident-api' => 'Access accident API',
                                    'openid' => 'OpenID Connect',
                                    'profile' => 'User profile'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

    }

    // Minimal YAML dumper for associative arrays
    private static function yamlDump(array $data, int $indent = 0): string
    {
        $yaml = '';
        foreach ($data as $key => $value) {
            $pad = str_repeat('  ', $indent);
            if (is_array($value)) {
                $yaml .= "$pad$key:\n" . self::yamlDump($value, $indent + 1);
            } else {
                $yaml .= "$pad$key: $value\n";
            }
        }
        return $yaml;
    }
}
