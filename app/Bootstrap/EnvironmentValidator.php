<?php

declare(strict_types=1);

namespace App\Bootstrap;

use RuntimeException;

final readonly class EnvironmentValidator
{
    public static function validate(): void
    {
        if (env('APP_ENV') === 'testing') {
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

        foreach (EnvironmentVariables::REQUIRED_BOOTSTRAP as $key) {
            $value = env($key);

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
        $environment = env('APP_ENV');

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
        $appKey = env('APP_KEY');

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
        $connection = env('DB_CONNECTION');

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
        foreach (EnvironmentVariables::BOOLEAN_KEYS as $key) {
            self::validateBoolean($key);
        }
    }

    private static function validateBoolean(string $key): void
    {
        $value = env($key);

        if ($value === null || $value === '') {
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
        foreach (EnvironmentVariables::NUMERIC_KEYS as $key) {
            self::validateInteger($key);
        }
    }

    private static function validateInteger(string $key): void
    {
        $value = env($key);
        if ($value === null || $value === '') {
            return;
        }
        if (!is_numeric($value)) {
            self::fail([
                sprintf('%s must be numeric', $key),
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
        $value = env($key);

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

    private static function fail(array $errors): void
    {
        if (PHP_SAPI === 'cli') {
            logger()->warning(
                'Environment validation failed (deployment may proceed but check .env): {errors}',
                ['errors' => implode('; ', $errors)]
            );

            return;
        }

        throw new RuntimeException(
            implode('; ', $errors)
        );
    }
}