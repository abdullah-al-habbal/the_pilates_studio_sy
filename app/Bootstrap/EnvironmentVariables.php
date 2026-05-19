<?php
declare(strict_types=1);

namespace App\Bootstrap;

final readonly class EnvironmentVariables
{
    public const REQUIRED = [
        'APP_NAME',
        'APP_ENV',
        'APP_KEY',
        'APP_DEBUG',
        'APP_URL',
        'DB_CONNECTION',
        'DB_HOST',
        'DB_PORT',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
        'CACHE_STORE',
        'QUEUE_CONNECTION',
        'SESSION_DRIVER',
        'AUTH_GUARD',
        'AUTH_PASSWORD_BROKER',
        'AUTH_MODEL',
        'AUTH_PASSWORD_RESET_TOKEN_TABLE',
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
    public const BOOLEAN_VALUES = [
        true,
        false,
    ];
}
