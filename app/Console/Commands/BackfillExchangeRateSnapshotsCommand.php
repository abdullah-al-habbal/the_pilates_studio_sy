<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Currency;
use App\Models\MerchandiseOrder;
use App\Models\Refund;
use Illuminate\Console\Command;

final class BackfillExchangeRateSnapshotsCommand extends Command
{
    protected $signature = 'finance:backfill-exchange-snapshots {--dry-run : Report only, do not write}';

    protected $description = 'Backfill missing exchange_rate_snapshot on financial records using current currency rates';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;

        foreach ([Booking::class, MerchandiseOrder::class, Refund::class] as $modelClass) {
            $query = $modelClass::query()->whereNull('exchange_rate_snapshot');

            $count = $query->count();
            $this->line("{$modelClass}: {$count} row(s) missing snapshot");

            if ($dryRun || $count === 0) {
                continue;
            }

            $query->chunkById(100, function ($rows) use (&$updated): void {
                foreach ($rows as $row) {
                    $currency = Currency::find($row->currency_id);
                    if ($currency === null) {
                        continue;
                    }

                    $row->update([
                        'exchange_rate_snapshot' => (float) $currency->exchange_rate,
                    ]);
                    $updated++;
                }
            });
        }

        $this->info($dryRun
            ? 'Dry run complete.'
            : "Backfilled {$updated} record(s).");

        return self::SUCCESS;
    }
}
