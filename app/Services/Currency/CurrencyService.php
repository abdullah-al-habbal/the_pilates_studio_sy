<?php

declare(strict_types=1);

namespace App\Services\Currency;

use App\Models\Currency;
use Illuminate\Support\Collection;

class CurrencyService
{
    public function getBaseCurrency(): Currency
    {
        $code = config('currency.base_currency', 'USD');
        return Currency::where('code', strtoupper($code))
            ->where('is_active', true)
            ->firstOrFail();
    }

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

    public function formatAmount(int $amount, ?int $currencyId = null): string
    {
        $currency = $currencyId ? Currency::find($currencyId) : $this->getDefaultCurrency();
        if (!$currency) {
            return (string) $amount;
        }
        $formattedNumber = number_format($amount, $currency->decimal_places);
        return $formattedNumber . ' ' . $currency->symbol;
    }

    public function getAllActiveCurrencies(): Collection
    {
        return Currency::where('is_active', true)->get();
    }
}