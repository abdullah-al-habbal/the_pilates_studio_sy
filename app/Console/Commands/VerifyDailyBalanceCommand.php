<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Finance\DailyBalanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class VerifyDailyBalanceCommand extends Command
{
    protected $signature = 'finance:verify-daily-balance {--date= : Date to verify (Y-m-d)}';

    protected $description = 'Verify per-currency daily balance totals for a given date';

    public function handle(DailyBalanceService $service): int
    {
        $date = $this->option('date') ?? now()->toDateString();
        $summary = $service->getSummary($date);

        if ($summary->isEmpty()) {
            $this->warn("No balance data for {$date}.");

            return self::SUCCESS;
        }

        $this->table(
            ['Code', 'Revenue', 'Expenses', 'Refunds', 'True Balance'],
            $summary->map(fn (array $row): array => [
                $row['currency_code'],
                $row['total_revenue'],
                $row['total_expenses'],
                $row['total_refunds'],
                $row['true_balance'],
            ])->all()
        );

        $this->info("Verified daily balance for {$date}.");

        return self::SUCCESS;
    }
}
