<?php
namespace App\Helpers;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ApiController
{
    public function __construct() {}
}

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public string $path,
        public string $method = 'GET'
    ) {}
}
