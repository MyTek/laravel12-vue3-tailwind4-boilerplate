<?php

return [
    'paths' => ['*'],
    'allowed_methods' => ['*'],

    // Do NOT use allowed_origins ['*'] when supports_credentials is true.
    // Use patterns so Laravel reflects the requesting Origin.
    'allowed_origins' => [],
    'allowed_origins_patterns' => ['*'],

    'allowed_headers' => ['*'],
    'exposed_headers' => ['*'],
    'max_age' => 0,
    'supports_credentials' => true,
];
