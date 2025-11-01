<?php

// Versioned API (guarded by ApiKeyAuth in index.php)
// $router->get('/v1/users', [new UserController(), 'index']);
$router->get('/', function ($req, $res) {
    $html = file_get_contents(__DIR__ . '/../docs/index.php');
    return $res->text($html, 200, 'text/html');
});
