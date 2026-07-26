<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\LogRecord;

final class TimestampLogHandler extends StreamHandler
{
    private ?string $currentDate = null;

    public function write(LogRecord $record): void
    {
        $date = $record->datetime->format('Y-m-d');

        if ($this->currentDate !== $date) {
            $this->close();
            $this->currentDate = $date;

            $dir = $this->getLogDirectory($record);

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $this->url = $dir.DIRECTORY_SEPARATOR.'laravel.log';
        }

        parent::write($record);
    }

    private function getLogDirectory(LogRecord $record): string
    {
        $year = $record->datetime->format('Y');
        $month = $record->datetime->format('m');
        $day = $record->datetime->format('d');

        return $this->streamPath.DIRECTORY_SEPARATOR.$year.DIRECTORY_SEPARATOR.$month.DIRECTORY_SEPARATOR.$day;
    }
}
