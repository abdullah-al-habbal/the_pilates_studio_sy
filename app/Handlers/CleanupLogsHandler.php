<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Dtos\CleanupLogsResult;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class CleanupLogsHandler
{
    public function handle(int $days = 7): CleanupLogsResult
    {
        $cutoff = now()->subDays($days);
        $logsPath = storage_path('logs');

        if (! is_dir($logsPath)) {
            return new CleanupLogsResult(
                deletedDays: 0,
                messages: ["Logs directory does not exist: {$logsPath}"],
            );
        }

        $messages = [];
        $deletedDays = 0;

        foreach ($this->getDirectories($logsPath) as $year) {
            if (! ctype_digit($year)) {
                continue;
            }

            [$yearDeleted, $yearMessages] = $this->cleanupYear(
                yearPath: $logsPath.DIRECTORY_SEPARATOR.$year,
                year: $year,
                cutoff: $cutoff,
            );

            $deletedDays += $yearDeleted;
            $messages = array_merge($messages, $yearMessages);
        }

        $messages[] = "Done. Deleted {$deletedDays} day director".($deletedDays === 1 ? 'y.' : 'ies.');

        return new CleanupLogsResult(
            deletedDays: $deletedDays,
            messages: $messages,
        );
    }

    /**
     * @return array{int, list<string>}
     */
    private function cleanupYear(string $yearPath, string $year, Carbon $cutoff): array
    {
        $deletedDays = 0;
        $messages = [];

        foreach ($this->getDirectories($yearPath) as $month) {
            if (! ctype_digit($month)) {
                continue;
            }

            [$monthDeleted, $monthMessages] = $this->cleanupMonth(
                monthPath: $yearPath.DIRECTORY_SEPARATOR.$month,
                year: $year,
                month: $month,
                cutoff: $cutoff,
            );

            $deletedDays += $monthDeleted;
            $messages = array_merge($messages, $monthMessages);
        }

        $this->cleanupEmptyDirectory($yearPath, $messages);

        return [$deletedDays, $messages];
    }

    /**
     * @return array{int, list<string>}
     */
    private function cleanupMonth(string $monthPath, string $year, string $month, Carbon $cutoff): array
    {
        $deletedDays = 0;
        $messages = [];

        foreach ($this->getDirectories($monthPath) as $day) {
            if (! ctype_digit($day)) {
                continue;
            }

            $message = $this->cleanupDay(
                dayPath: $monthPath.DIRECTORY_SEPARATOR.$day,
                year: $year,
                month: $month,
                day: $day,
                cutoff: $cutoff,
            );

            if ($message !== null) {
                $deletedDays++;
                $messages[] = $message;
            }
        }

        $this->cleanupEmptyDirectory($monthPath, $messages);

        return [$deletedDays, $messages];
    }

    private function cleanupDay(string $dayPath, string $year, string $month, string $day, Carbon $cutoff): ?string
    {
        try {
            $date = Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-{$day}");
        } catch (InvalidFormatException) {
            return null;
        }

        if ($date->startOfDay()->lt($cutoff)) {
            $this->deleteDayDirectory($dayPath);

            return "Deleted: {$year}/{$month}/{$day}";
        }

        return null;
    }

    private function getDirectories(string $path): array
    {
        $directories = [];

        foreach (new \DirectoryIterator($path) as $item) {
            if ($item->isDot() || ! $item->isDir()) {
                continue;
            }

            $directories[] = $item->getFilename();
        }

        return $directories;
    }

    private function deleteDayDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        rmdir($path);
    }

    private function cleanupEmptyDirectory(string $path, array &$messages): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = array_diff(scandir($path) ?: [], ['.', '..']);

        if ($items === []) {
            rmdir($path);
            $messages[] = 'Removed empty directory: '.basename($path);
        }
    }
}
