<?php

declare(strict_types=1);

namespace App\Bootstrap;

use Dotenv\Dotenv;
use RuntimeException;

final readonly class EnvironmentValidator
{
    public static function validate(): void
    {
        if (getenv('APP_ENV') === 'testing' || ($_ENV['APP_ENV'] ?? '') === 'testing') {
            return;
        }

        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            self::fail(['.env file not found at ' . $envPath]);
        }

        $envContent = file_get_contents($envPath);
        $envValues = Dotenv::parse($envContent);

        self::validateRequiredVariables($envValues);
        self::validateEnvironment($envValues);
        self::validateAppKey($envValues);
        self::validateBooleanValues($envValues);
        self::validateNumericValues($envValues);
        self::validateUrlValues($envValues);
    }

    private static function validateRequiredVariables(array $env): void
    {
        $missing = [];

        foreach (EnvironmentVariables::REQUIRED_BOOTSTRAP as $key) {
            if (!array_key_exists($key, $env) || $env[$key] === '' || $env[$key] === null) {
                $missing[] = "Missing required environment variable: {$key}";
            }
        }

        if ($missing !== []) {
            self::fail($missing);
        }
    }

    private static function validateEnvironment(array $env): void
    {
        $environment = $env['APP_ENV'] ?? '';

        if (
            !is_string($environment)
            || !in_array($environment, EnvironmentVariables::ALLOWED_ENVIRONMENTS, true)
        ) {
            self::fail([
                sprintf(
                    'Invalid APP_ENV [%s]. Allowed values: %s',
                    (string) $environment,
                    implode(', ', EnvironmentVariables::ALLOWED_ENVIRONMENTS)
                ),
            ]);
        }
    }

    private static function validateAppKey(array $env): void
    {
        $appKey = $env['APP_KEY'] ?? '';

        if (!is_string($appKey) || !preg_match('/^base64:[A-Za-z0-9+\/=]+$/', $appKey)) {
            self::fail([
                'Invalid APP_KEY format. Run: php artisan key:generate',
            ]);
        }
    }

    private static function validateBooleanValues(array $env): void
    {
        foreach (EnvironmentVariables::BOOLEAN_KEYS as $key) {
            self::validateBoolean($key, $env);
        }
    }

    private static function validateBoolean(string $key, array $env): void
    {
        $value = $env[$key] ?? null;

        if ($value === null || $value === '') {
            return;
        }

        if (!in_array($value, EnvironmentVariables::BOOLEAN_VALUES, true)) {
            self::fail([
                sprintf('%s must be a boolean value [true|false]', $key),
            ]);
        }
    }

    private static function validateNumericValues(array $env): void
    {
        foreach (EnvironmentVariables::NUMERIC_KEYS as $key) {
            self::validateInteger($key, $env);
        }
    }

    private static function validateInteger(string $key, array $env): void
    {
        $value = $env[$key] ?? null;

        if ($value === null || $value === '') {
            return;
        }

        if (!is_numeric($value)) {
            self::fail([
                sprintf('%s must be numeric', $key),
            ]);
        }
    }

    private static function validateUrlValues(array $env): void
    {
        $keys = ['APP_URL'];
        foreach ($keys as $key) {
            self::validateUrl($key, $env);
        }
    }

    private static function validateUrl(string $key, array $env): void
    {
        $value = $env[$key] ?? null;

        if ($value === null) {
            return;
        }

        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            self::fail([
                sprintf('%s must be a valid URL', $key),
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

        throw new RuntimeException(implode('; ', $errors));
    }
}
