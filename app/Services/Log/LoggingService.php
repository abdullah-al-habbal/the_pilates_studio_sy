<?php
// filePath: app/Services/Log/LoggingService.php

declare(strict_types=1);

namespace App\Services\Log;

use Illuminate\Support\Facades\Log;

class LoggingService
{
    public function info(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }
}
