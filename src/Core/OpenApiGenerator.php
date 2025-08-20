<?php
namespace Core;

use ReflectionClass;
use ReflectionMethod;
use App\Route;

class OpenApiGenerator
{
    public static function generateYaml(array $controllers, string $outputFile): void
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
        $openapi = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'PHP API Starter',
                'version' => '1.0.0',
                'description' => 'Auto-generated OpenAPI spec.'
            ],
            'servers' => [['url' => '/']],
            'paths' => $paths
        ];
        file_put_contents($outputFile, self::yamlDump($openapi));
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
