<?php

return [
    'paths' => ['api/*', 'logout'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:5011', 'http://127.0.0.1:5011'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
