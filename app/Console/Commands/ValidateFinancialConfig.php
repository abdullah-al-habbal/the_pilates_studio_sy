<?php

// app\Console\Commands\ValidateFinancialConfig.php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Currency;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('config:validate-financial')]
#[Description('Validate financial configuration integrity')]
class ValidateFinancialConfig extends Command
{
    public function handle(): int
    {
        $errors = [];

        $baseCode = config('currency.base_currency');
        $baseCurrency = Currency::where('code', $baseCode)->where('is_active', true)->first();

        if (! $baseCurrency) {
            $errors[] = "Base currency '{$baseCode}' not found or not active";
        }

        $precision = config('currency.snapshot_precision');
        if (
            $precision < config('currency.validation.snapshot_precision_min', 4)
            || $precision > config('currency.validation.snapshot_precision_max', 8)
        ) {
            $errors[] = "Snapshot precision {$precision} out of allowed range [4-8]";
        }

        $invalidRates = Currency::where('is_active', true)
            ->where('exchange_rate', '<=', 0)
            ->pluck('code')
            ->toArray();

        if (! empty($invalidRates)) {
            $errors[] = 'Invalid exchange rates (<=0) for currencies: '.implode(', ', $invalidRates);
        }

        if (empty($errors)) {
            $this->info('✅ Financial configuration validated successfully');

            return self::SUCCESS;
        }

        $this->error('❌ Financial configuration validation failed:');
        foreach ($errors as $error) {
            $this->line("  • {$error}");
        }

        return self::FAILURE;
    }
}
