<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Handlers\CleanupLogsHandler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('logs:cleanup {--days=7 : Number of days to keep}')]
#[Description('Delete log directories older than the specified number of days and remove empty folders')]
class CleanupLogsCommand extends Command
{
    public function __construct(
        private readonly CleanupLogsHandler $handler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->handler->handle(days: (int) $this->option('days'));

        foreach ($result->messages as $message) {
            $this->info($message);
        }

        return self::SUCCESS;
    }
}
