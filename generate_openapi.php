<?php
require_once __DIR__ . '/src/App/Controllers/HomeController.php';
require_once __DIR__ . '/src/App/Controllers/UserController.php';
require_once __DIR__ . '/src/Core/OpenApiGenerator.php';

use Core\OpenApiGenerator;
use App\Controllers\HomeController;
use App\Controllers\UserController;

OpenApiGenerator::generateYaml([
    new HomeController(),
    new UserController()
], __DIR__ . '/public/test.yaml');

echo "OpenAPI spec generated in public/openapi.yaml\n";
