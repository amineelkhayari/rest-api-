<?php
namespace App\config;

class App
{
    public static function get(): array
    {
        return [
            'app_name' => 'PHP API Starter',
            'env' => 'local',
            'debug' => true,
            'base_path' => dirname(__DIR__, 2),
            'api_key' => 'changeme-secret',
            'oAuth' => [
                'clientId' => 'accident-spa',
                'clientSecret' => '',
                'scopes' => 'openid profile accident-api',
                'issuer' => 'https://localhost:5001',
                'audience' => 'accident',
                'redrectUrl' => 'http://localhost:8080/pub-api/public/oauth2-redirect.html'
            ]
        ];
    }
}
