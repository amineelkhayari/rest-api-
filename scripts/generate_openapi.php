<?php
// Simple script to generate OpenAPI YAML and JSON from attribute routes
// Ensure autoloading is available (same as public/index.php)
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require $composerAutoload;
} else {
    // Minimal PSR-4-ish autoloader for this project
    spl_autoload_register(function ($class) {
        $prefixes = [
            'App\\' => __DIR__ . '/../src/App/',
            'Core\\' => __DIR__ . '/../src/Core/'
        ];
        foreach ($prefixes as $prefix => $baseDir) {
            if (strncmp($class, $prefix, strlen($prefix)) === 0) {
                $relative = substr($class, strlen($prefix));
                $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
                if (file_exists($file)) require $file;
            }
        }
    });
}

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../src/Core/OpenApiGenerator.php';

use Core\OpenApiGenerator;

// discover controllers same as loader
$dir = __DIR__ . '/../src/App/Controllers';
$controllers = [];
if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if (in_array($file, ['.', '..'])) continue;
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;
        $className = pathinfo($file, PATHINFO_FILENAME);
        $fqcn = 'App\\Controllers\\' . $className;
        if (class_exists($fqcn)) {
            $controllers[] = new $fqcn();
        } else {
            require_once $dir . '/' . $file;
            if (class_exists($fqcn)) $controllers[] = new $fqcn();
        }
    }
}

$outYaml = __DIR__ . '/../public/test.yaml';
$outJson = __DIR__ . '/../public/test.json';

OpenApiGenerator::generateYaml($controllers, $outYaml);
OpenApiGenerator::generateJson($controllers, $outJson);

echo "Wrote $outYaml and $outJson\n";
