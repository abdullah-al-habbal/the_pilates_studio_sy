<?php
// filePath: config/app.php
declare(strict_types=1);

return [
    'name' => env('APP_NAME'),
    'env' => env('APP_ENV'),
    'debug' => (bool) env('APP_DEBUG'),
    'url' => env('APP_URL'),
    'timezone' => 'UTC',
    'locale' => env('APP_LOCALE'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE'),
    'faker_locale' => env('APP_FAKER_LOCALE'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER'),
        'store' => env('APP_MAINTENANCE_STORE'),
    ],
    'force_https' => false,
];
