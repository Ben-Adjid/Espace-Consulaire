<?php

// CORS : seul le frontend Next.js (FRONTEND_URL) peut appeler l'API depuis un navigateur.
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
    'allowed_origins' => array_filter([env('FRONTEND_URL', 'http://localhost:3000')]),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Content-Type', 'X-Requested-With', 'Accept-Language'],
    'exposed_headers' => [],
    'max_age' => 600,
    'supports_credentials' => false,
];
