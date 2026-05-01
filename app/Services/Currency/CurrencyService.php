<?php

declare(strict_types=1);

namespace App\Services\Currency;

use App\Models\Currency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    public function getDefaultCurrency(): Currency
    {
        return Currency::where('code', 'USD')->where('is_active', true)->firstOrFail();
    }

    public function getCurrencyIdByCode(string $code): ?int
    {
        return Currency::where('code', strtoupper($code))->where('is_active', true)->value('id');
    }

    public function getSymbol(?int $currencyId = null): string
    {
        $currency = $currencyId ? Currency::find($currencyId) : $this->getDefaultCurrency();
        return $currency?->symbol ?? '';
    }

    public function getCode(?int $currencyId = null): string
    {
        $currency = $currencyId ? Currency::find($currencyId) : $this->getDefaultCurrency();
        return $currency?->code ?? 'USD';
    }

    public function getDecimalPlaces(?int $currencyId = null): int
    {
        $currency = $currencyId ? Currency::find($currencyId) : $this->getDefaultCurrency();
        return $currency?->decimal_places ?? 2;
    }

    public function formatAmount(int $amountInSmallestUnit, ?int $currencyId = null): string
    {
        $currency = $currencyId ? Currency::find($currencyId) : $this->getDefaultCurrency();
        if (!$currency) {
            return (string) $amountInSmallestUnit;
        }
        $divisor = 10 ** $currency->decimal_places;
        $formattedNumber = number_format($amountInSmallestUnit / $divisor, $currency->decimal_places);
        return $formattedNumber . ' ' . $currency->symbol;
    }

    public function toSmallestUnit(float $amount, ?int $currencyId = null): int
    {
        $decimalPlaces = $this->getDecimalPlaces($currencyId);
        return (int) round($amount * (10 ** $decimalPlaces));
    }

    public function getAllActiveCurrencies(): Collection
    {
        return Cache::remember('active_currencies', 3600, fn() => Currency::where('is_active', true)->get());
    }
}
