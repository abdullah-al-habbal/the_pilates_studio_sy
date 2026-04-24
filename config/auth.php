<?php
// config\auth.php
declare(strict_types=1);
return [
    'return_otp_in_response' => env('RETURN_OTP_IN_RESPONSE'),
    'defaults' => [
        'guard' => env('AUTH_GUARD'),
        'passwords' => env('AUTH_PASSWORD_BROKER'),
    ],
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL'),
        ],
    ],
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT'),
];
