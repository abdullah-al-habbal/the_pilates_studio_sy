<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EnvironmentValidator
{
    protected array $rules = [
        'APP_ENV' => 'required|in:local,production,staging',
        'APP_KEY' => 'required|regex:/^base64:[A-Za-z0-9+\/=]+$/',
        'APP_DEBUG' => 'required|in:true,false',
        'APP_URL' => 'required|url',
        'DB_CONNECTION' => 'required|in:mysql,pgsql,sqlite',
        'DB_HOST' => 'required_if:DB_CONNECTION,mysql,pgsql',
        'DB_PORT' => 'required_if:DB_CONNECTION,mysql,pgsql|numeric',
        'DB_DATABASE' => 'required',
        'DB_USERNAME' => 'required',
        'DB_PASSWORD' => 'required',
        'AUTH_GUARD' => 'required|in:web,api',
        'AUTH_PASSWORD_BROKER' => 'required|in:users',
        'AUTH_MODEL' => 'required|in:App\Models\User',
        'AUTH_PASSWORD_RESET_TOKEN_TABLE' => 'required|string',
        'SESSION_DRIVER' => 'required|in:file,cookie,database,redis',
        'QUEUE_CONNECTION' => 'required|in:sync,database,redis',
        'CACHE_STORE' => 'required|in:file,database,redis',
    ];

    protected array $messages = [
        'APP_ENV.required' => 'APP_ENV is missing in .env',
        'APP_ENV.in' => 'APP_ENV must be one of: local, production, staging',
        'APP_KEY.required' => 'APP_KEY is missing. Run `php artisan key:generate`',
        'APP_KEY.regex' => 'APP_KEY is malformed. Run `php artisan key:generate`',
        'APP_DEBUG.required' => 'APP_DEBUG is missing (must be true or false)',
        'APP_DEBUG.in' => 'APP_DEBUG must be either "true" or "false"',
        'APP_URL.required' => 'APP_URL is missing in .env',
        'APP_URL.url' => 'APP_URL must be a valid URL',

        'DB_CONNECTION.required' => 'DB_CONNECTION is missing (mysql, pgsql, or sqlite)',
        'DB_CONNECTION.in' => 'DB_CONNECTION must be mysql, pgsql, or sqlite',
        'DB_HOST.required_if' => 'DB_HOST is required when using MySQL or PostgreSQL',
        'DB_PORT.required_if' => 'DB_PORT is required when using MySQL or PostgreSQL',
        'DB_PORT.numeric' => 'DB_PORT must be a number',
        'DB_DATABASE.required' => 'DB_DATABASE is missing',
        'DB_USERNAME.required' => 'DB_USERNAME is missing',
        'DB_PASSWORD.required' => 'DB_PASSWORD is missing',

        'AUTH_GUARD.required' => 'AUTH_GUARD is missing (should be "web")',
        'AUTH_GUARD.in' => 'AUTH_GUARD must be "web" or "api"',
        'AUTH_PASSWORD_BROKER.required' => 'AUTH_PASSWORD_BROKER is missing (should be "users")',
        'AUTH_PASSWORD_BROKER.in' => 'AUTH_PASSWORD_BROKER must be "users"',
        'AUTH_MODEL.required' => 'AUTH_MODEL is missing (should be "App\Models\User")',
        'AUTH_MODEL.in' => 'AUTH_MODEL must be "App\Models\User"',
        'AUTH_PASSWORD_RESET_TOKEN_TABLE.required' => 'AUTH_PASSWORD_RESET_TOKEN_TABLE is missing (e.g., "password_reset_tokens")',
        'AUTH_PASSWORD_RESET_TOKEN_TABLE.string' => 'AUTH_PASSWORD_RESET_TOKEN_TABLE must be a string',

        'SESSION_DRIVER.required' => 'SESSION_DRIVER is missing (file, cookie, database, or redis)',
        'SESSION_DRIVER.in' => 'SESSION_DRIVER must be file, cookie, database, or redis',
        'QUEUE_CONNECTION.required' => 'QUEUE_CONNECTION is missing (sync, database, or redis)',
        'QUEUE_CONNECTION.in' => 'QUEUE_CONNECTION must be sync, database, or redis',
        'CACHE_STORE.required' => 'CACHE_STORE is missing (file, database, or redis)',
        'CACHE_STORE.in' => 'CACHE_STORE must be file, database, or redis',
    ];

    public function validate(): bool
    {
        $data = $_ENV;
        $validator = Validator::make($data, $this->rules, $this->messages);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $errorString = implode('; ', $errors);
            Log::critical('Environment validation failed: ' . $errorString);

            if (app()->runningInConsole()) {
                echo "\n❌ Environment validation failed:\n";
                foreach ($errors as $error) {
                    echo "  - $error\n";
                }
                echo "\nPlease fix your .env file and try again.\n";
                exit(1);
            }

            $this->showFatalError($errors);
            return false;
        }

        return true;
    }

    protected function showFatalError(array $errors): void
    {
        if (!headers_sent()) {
            http_response_code(500);
        }

        echo '<!DOCTYPE html><html><head><title>Configuration Error</title><style>
            body{font-family:sans-serif;margin:40px;background:#f8fafc;}
            .error-box{background:white;border-left:5px solid #e53e3e;padding:20px;border-radius:5px;box-shadow:0 1px 3px rgba(0,0,0,0.1);}
            ul{margin:10px 0;}
            li{margin:5px 0;}
        </style></head><body>';
        echo '<div class="error-box"><h1>⚠️ Environment Configuration Error</h1><p>The application cannot run because of missing or invalid environment variables:</p><ul>';
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo '</ul><p>Please contact the system administrator.</p></div></body></html>';
        exit(1);
    }
}