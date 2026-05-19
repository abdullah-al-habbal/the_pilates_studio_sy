<?php

declare(strict_types=1);

namespace App\Bootstrap;

use Illuminate\Support\Env;
use RuntimeException;

final readonly class EnvironmentValidator
{
    public static function validate(): void
    {
        if (Env::get('APP_ENV') === 'testing') {
            return;
        }

        self::validateRequiredVariables();
        self::validateEnvironment();
        self::validateAppKey();
        self::validateDatabaseConnection();
        self::validateBooleanValues();
        self::validateNumericValues();
        self::validateUrlValues();
    }

    private static function validateRequiredVariables(): void
    {
        $missing = [];

        foreach (EnvironmentVariables::REQUIRED as $key) {
            $value = Env::get($key);

            if ($value === null) {
                $missing[] = "Missing required environment variable: {$key}";
            }
        }

        if ($missing !== []) {
            self::fail($missing);
        }
    }

    private static function validateEnvironment(): void
    {
        $environment = Env::get('APP_ENV');

        if (
            !is_string($environment)
            || !in_array(
                $environment,
                EnvironmentVariables::ALLOWED_ENVIRONMENTS,
                true
            )
        ) {
            self::fail([
                sprintf(
                    'Invalid APP_ENV [%s]. Allowed values: %s',
                    (string) $environment,
                    implode(
                        ', ',
                        EnvironmentVariables::ALLOWED_ENVIRONMENTS
                    )
                ),
            ]);
        }
    }

    private static function validateAppKey(): void
    {
        $appKey = Env::get('APP_KEY');

        if (
            !is_string($appKey)
            || !preg_match(
                '/^base64:[A-Za-z0-9+\/=]+$/',
                $appKey
            )
        ) {
            self::fail([
                'Invalid APP_KEY format. Run: php artisan key:generate',
            ]);
        }
    }

    private static function validateDatabaseConnection(): void
    {
        $connection = Env::get('DB_CONNECTION');

        if (
            !is_string($connection)
            || !in_array(
                $connection,
                EnvironmentVariables::ALLOWED_DATABASE_DRIVERS,
                true
            )
        ) {
            self::fail([
                sprintf(
                    'Invalid DB_CONNECTION [%s]. Allowed values: %s',
                    (string) $connection,
                    implode(
                        ', ',
                        EnvironmentVariables::ALLOWED_DATABASE_DRIVERS
                    )
                ),
            ]);
        }
    }

    private static function validateBooleanValues(): void
    {
        $keys = [
            'APP_DEBUG',
            'SESSION_SECURE_COOKIE',
            'SESSION_ENCRYPT',
            'AWS_USE_PATH_STYLE_ENDPOINT',
            'RETURN_OTP_IN_RESPONSE',
        ];

        foreach ($keys as $key) {
            self::validateBoolean($key);
        }
    }

    private static function validateBoolean(string $key): void
    {
        $value = Env::get($key);

        if ($value === null) {
            return;
        }

        if (
            !in_array(
                $value,
                EnvironmentVariables::BOOLEAN_VALUES,
                true
            )
        ) {
            self::fail([
                sprintf(
                    '%s must be a boolean value [true|false]',
                    $key
                ),
            ]);
        }
    }

    private static function validateNumericValues(): void
    {
        $keys = [
            'DB_PORT',
            'BCRYPT_ROUNDS',
            'AUTH_PASSWORD_TIMEOUT',
            'SESSION_LIFETIME',
        ];

        foreach ($keys as $key) {
            self::validateInteger($key);
        }
    }

    private static function validateInteger(string $key): void
    {
        $value = Env::get($key);

        if ($value === null) {
            return;
        }

        if (!is_numeric($value)) {
            self::fail([
                sprintf(
                    '%s must be numeric',
                    $key
                ),
            ]);
        }
    }

    private static function validateUrlValues(): void
    {
        $keys = [
            'APP_URL',
        ];

        foreach ($keys as $key) {
            self::validateUrl($key);
        }
    }

    private static function validateUrl(string $key): void
    {
        $value = Env::get($key);

        if ($value === null) {
            return;
        }

        if (
            !is_string($value)
            || filter_var($value, FILTER_VALIDATE_URL) === false
        ) {
            self::fail([
                sprintf(
                    '%s must be a valid URL',
                    $key
                ),
            ]);
        }
    }

    private static function fail(array $errors): never
    {
        $message = implode(
            PHP_EOL,
            array_map(
                static fn(string $error): string => "  - {$error}",
                $errors
            )
        );

        if (PHP_SAPI === 'cli') {
            fwrite(
                STDERR,
                PHP_EOL
                . '❌ Environment validation failed:'
                . PHP_EOL
                . $message
                . PHP_EOL
                . PHP_EOL
                . 'Please fix your .env file and try again.'
                . PHP_EOL
            );

            exit(1);
        }

        throw new RuntimeException(
            implode('; ', $errors)
        );
    }
}