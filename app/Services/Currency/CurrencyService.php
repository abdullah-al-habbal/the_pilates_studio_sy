<?php

declare(strict_types=1);

namespace App\Services\Currency;

use App\Exceptions\CurrencyNotFoundException;
use App\Models\Currency;
use Illuminate\Support\Collection;

class CurrencyService
{
    public function getBaseCurrency(): Currency
    {
        $code = config('currency.base_currency');
        return Currency::where('code', strtoupper($code))
            ->where('is_active', true)
            ->firstOr(fn() => throw CurrencyNotFoundException::forCode($code));
    }

    public function getDefaultCurrency(): Currency
    {
        $code = config('currency.default_currency');
        return Currency::where('code', strtoupper($code))
            ->where('is_active', true)
            ->firstOr(fn() => throw CurrencyNotFoundException::forCode($code));
    }

    public function getCurrencyIdByCode(string $code): ?int
    {
        return Currency::where('code', strtoupper($code))
            ->where('is_active', true)
            ->value('id');
    }

    public function getSymbol(?int $currencyId = null): string
    {
        $currency = $currencyId
            ? Currency::find($currencyId)
            : $this->getDefaultCurrency();

        if (! $currency) {
            throw CurrencyNotFoundException::forId($currencyId ?? 0);
        }

        return $currency->symbol;
    }

    public function getCode(?int $currencyId = null): string
    {
        $currency = $currencyId
            ? Currency::find($currencyId)
            : $this->getDefaultCurrency();

        if (! $currency) {
            throw CurrencyNotFoundException::forId($currencyId ?? 0);
        }

        return $currency->code;
    }

    public function getDecimalPlaces(?int $currencyId = null): int
    {
        $currency = $currencyId
            ? Currency::find($currencyId)
            : $this->getDefaultCurrency();

        if (! $currency) {
            throw CurrencyNotFoundException::forId($currencyId ?? 0);
        }

        return $currency->decimal_places;
    }

    public function formatAmount(int $amount, ?int $currencyId = null): string
    {
        $currency = $currencyId
            ? Currency::find($currencyId)
            : $this->getDefaultCurrency();

        if (! $currency) {
            throw CurrencyNotFoundException::forId($currencyId ?? 0);
        }

        $formattedNumber = number_format($amount, $currency->decimal_places);
        return $formattedNumber . ' ' . $currency->symbol;
    }

    public function getAllActiveCurrencies(): Collection
    {
        return Currency::where('is_active', true)->get();
    }
}
