<?php
declare(strict_types=1);
namespace App\Listeners;

use App\Models\Currency;
use App\Services\Currency\CurrencyService;

class RefreshCurrencyCacheOnUpdate
{
    public function __construct(
        private readonly CurrencyService $currencyService
    ) {
    }

    public function handle(object $event): void
    {
        if ($event instanceof Currency && $event->wasChanged('exchange_rate')) {
            $this->currencyService->refreshCurrencyCache($event->id);
        }
    }
}
