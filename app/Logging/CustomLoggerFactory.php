<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Contracts\Logging\Log;
use Illuminate\Log\Logger;
use Psr\Log\LogLevel;

class CustomLoggerFactory
{
    public function __invoke(array $config): Log
    {
        $handler = new TimestampLogHandler(
            streamPath: $config['path'] ?? storage_path('logs'),
            level: $this->parseLevel($config['level'] ?? LogLevel::DEBUG),
            bubble: $config['bubble'] ?? true,
        );

        return new Logger(
            channel: 'custom',
            handlers: [$handler],
        );
    }

    private function parseLevel(string|int $level): Level
    {
        return match (strtolower((string) $level)) {
            'debug' => Level::Debug,
            'info' => Level::Info,
            'notice' => Level::Notice,
            'warning', 'warn' => Level::Warning,
            'error' => Level::Error,
            'critical' => Level::Critical,
            'alert' => Level::Alert,
            'emergency' => Level::Emergency,
            default => Level::Debug,
        };
    }
}
