<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://10.244.132.254:3000',
    ],
    'allowed_origins_patterns' => [
        '#^http://10\.244\.\d+\.\d+:3000$#',
        '#^http://192\.168\.\d+\.\d+:3000$#',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];