<?php

// config/scramble.php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    'api_path' => 'api/v1',

    'api_domain' => null,

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),

        'description' => 'Pilates API Documentation - A comprehensive API for managing pilates classes, bookings, and user profiles.',
    ],

    'servers' => null,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];
