<?php

declare(strict_types=1);

namespace App\Bootstrap;

final readonly class EnvironmentVariables
{
    public const REQUIRED_BOOTSTRAP = [
        'APP_NAME',
        'APP_ENV',
        'APP_KEY',
        'APP_DEBUG',
        'APP_URL',
        'DB_CONNECTION',
    ];

    public const BOOLEAN_KEYS = [
        'APP_DEBUG',
        'SESSION_SECURE_COOKIE',
        'SESSION_ENCRYPT',
        'AWS_USE_PATH_STYLE_ENDPOINT',
        'RETURN_OTP_IN_RESPONSE',
    ];

    public const BOOLEAN_VALUES = [
        true,
        false,
    ];

    public const NUMERIC_KEYS = [
        'DB_PORT',
        'BCRYPT_ROUNDS',
        'AUTH_PASSWORD_TIMEOUT',
        'SESSION_LIFETIME',
    ];

    public const ALLOWED_ENVIRONMENTS = [
        'local',
        'staging',
        'production',
        'testing',
    ];

    public const ALLOWED_DATABASE_DRIVERS = [
        'mysql',
        'pgsql',
        'sqlite',
    ];
}
