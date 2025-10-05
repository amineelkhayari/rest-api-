<?php
return [
    'app_name' => 'PHP API Starter',
    'env' => 'local',
    'debug' => true,
    'base_path' => dirname(__DIR__),
    'api_key' => 'changeme-secret',
    'oAuth' => [
        'clientId'=> "accident-spa",
        'clientSecret'=>'',
        'scopes' => 'openid profile accident-api',
        'issuer' => "https://localhost:5001",
        'audience' => "accident",
        'redrectUrl'=>'http://localhost/pub-api/public/oauth2-redirect.html'
    ]
];
