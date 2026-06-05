<?php

declare(strict_types=1);

namespace App\Data\Reports;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final readonly class CurrencySummaryData implements Arrayable, JsonSerializable
{
    public function __construct(
        public int $currencyId,
        public string $currencyCode,
        public string $currencySymbol,
        public int $currencyDecimals,
        public int $packageRevenue,
        public int $merchandiseRevenue,
        public int $totalRevenue,
        public int $totalExpenses,
        public int $totalRefunds,
        public int $trueBalance,
        public bool $baseConversionApplied,
        public ?string $baseCurrencyCode,
        public ?int $totalRevenueInBase,
        public ?int $trueBalanceInBase,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            currencyId: $data['currency_id'],
            currencyCode: $data['currency_code'],
            currencySymbol: $data['currency_symbol'],
            currencyDecimals: $data['currency_decimals'],
            packageRevenue: $data['package_revenue'],
            merchandiseRevenue: $data['merchandise_revenue'],
            totalRevenue: $data['total_revenue'],
            totalExpenses: $data['total_expenses'],
            totalRefunds: $data['total_refunds'],
            trueBalance: $data['true_balance'],
            baseConversionApplied: $data['base_conversion_applied'],
            baseCurrencyCode: $data['base_currency_code'] ?? null,
            totalRevenueInBase: $data['total_revenue_in_base'] ?? null,
            trueBalanceInBase: $data['true_balance_in_base'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'currency_id' => $this->currencyId,
            'currency_code' => $this->currencyCode,
            'currency_symbol' => $this->currencySymbol,
            'currency_decimals' => $this->currencyDecimals,
            'package_revenue' => $this->packageRevenue,
            'merchandise_revenue' => $this->merchandiseRevenue,
            'total_revenue' => $this->totalRevenue,
            'total_expenses' => $this->totalExpenses,
            'total_refunds' => $this->totalRefunds,
            'true_balance' => $this->trueBalance,
            'base_conversion_applied' => $this->baseConversionApplied,
            'base_currency_code' => $this->baseCurrencyCode,
            'total_revenue_in_base' => $this->totalRevenueInBase,
            'true_balance_in_base' => $this->trueBalanceInBase,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
