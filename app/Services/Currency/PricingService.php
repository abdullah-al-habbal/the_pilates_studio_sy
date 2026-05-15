<?php

declare(strict_types=1);

namespace App\Services\Currency;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;

final readonly class PricingService
{
    public function __construct(
        private CurrencyService $currencyService
    ) {
    }


    public function getBasePrice(object $priceable): ?int
    {
        $baseCurrencyId = $this->currencyService->getBaseCurrency()->id;

        return $priceable->prices()
            ->where('currency_id', $baseCurrencyId)
            ->value('amount');
    }

    public function calculateAmount(int $baseAmount, int $targetCurrencyId): int
    {
        if ($baseAmount <= 0) {
            return 0;
        }

        $targetCurrency = Currency::findOrFail($targetCurrencyId);
        $exchangeRate = $this->getExchangeRate($targetCurrency);

        if ($exchangeRate <= 0.0) {
            throw new \InvalidArgumentException(
                "Invalid exchange rate for currency: {$targetCurrency->code}"
            );
        }

        $baseCurrency = $this->currencyService->getBaseCurrency();
        $baseDivisor = 10 ** $baseCurrency->decimal_places;
        $targetDivisor = 10 ** $targetCurrency->decimal_places;

        $baseInUnits = $baseAmount / $baseDivisor;
        $convertedInTargetUnits = $baseInUnits * $exchangeRate;
        $convertedInSmallest = $convertedInTargetUnits * $targetDivisor;

        return (int) round($convertedInSmallest);
    }

    private function getExchangeRate(Currency $currency): float
    {
        $baseCode = strtoupper(config('currency.base_currency', 'USD'));

        if (strtoupper($currency->code) === $baseCode) {
            return 1.0;
        }

        return Cache::remember(
            "exchange_rate_{$currency->id}",
            300,
            fn() => $currency->exchange_rate
        );
    }

    public function getBaseCurrencyId(): int
    {
        return $this->currencyService->getBaseCurrency()->id;
    }

    /**
     * Returns the exchange rate to be snapshotted for a target currency.
     * Returns 1.0 if target IS the base currency.
     */
    public function getExchangeRateForSnapshot(int $targetCurrencyId): float
    {
        $targetCurrency = Currency::findOrFail($targetCurrencyId);
        $baseCode = strtoupper(config('currency.base_currency', 'USD'));

        if (strtoupper($targetCurrency->code) === $baseCode) {
            return 1.0;
        }

        return (float) $targetCurrency->exchange_rate;
    }
}
