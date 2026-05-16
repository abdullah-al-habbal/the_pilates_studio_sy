<?php
declare(strict_types=1);
namespace App\Data\Reports;

final readonly class CurrencySummaryData
{
    public function __construct(
        public int $currencyId,
        public string $currencyCode,
        public string $currencySymbol,
        public int $currencyDecimals,
        public int $totalRevenue,
        public int $packageRevenue,
        public int $merchandiseRevenue,
        public int $totalExpenses,
        public int $totalRefunds,
        public int $trueBalance,
        public bool $baseConversionApplied,
        public ?int $totalRevenueInBase,
        public ?int $trueBalanceInBase,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            currencyId: (int) $data['currency_id'],
            currencyCode: (string) $data['currency_code'],
            currencySymbol: (string) $data['currency_symbol'],
            currencyDecimals: (int) $data['currency_decimals'],
            totalRevenue: (int) $data['total_revenue'],
            packageRevenue: (int) $data['package_revenue'],
            merchandiseRevenue: (int) $data['merchandise_revenue'],
            totalExpenses: (int) $data['total_expenses'],
            totalRefunds: (int) $data['total_refunds'],
            trueBalance: (int) $data['true_balance'],
            baseConversionApplied: (bool) $data['base_conversion_applied'],
            totalRevenueInBase: isset($data['total_revenue_in_base'])
            ? (int) $data['total_revenue_in_base']
            : null,
            trueBalanceInBase: isset($data['true_balance_in_base'])
            ? (int) $data['true_balance_in_base']
            : null,
        );
    }
}
