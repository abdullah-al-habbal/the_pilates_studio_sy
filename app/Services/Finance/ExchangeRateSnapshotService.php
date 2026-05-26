<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Booking;
use App\Models\Currency;
use App\Models\MerchandiseOrder;
use App\Models\Refund;
use App\Services\Currency\CurrencyService;
use Carbon\CarbonInterface;

final readonly class ExchangeRateSnapshotService
{
    public function __construct(
        public CurrencyService $currencyService
    ) {}

    public function getHistoricalRate(int $currencyId, CarbonInterface $asOfDate): ?float
    {
        $baseCurrencyId = $this->currencyService->getBaseCurrency()->id;

        if ($currencyId === $baseCurrencyId) {
            return 1.0;
        }

        $snapshot = Booking::where('currency_id', $currencyId)
            ->whereDate('created_at', '<=', $asOfDate)
            ->whereNotNull('exchange_rate_snapshot')
            ->orderByDesc('created_at')
            ->value('exchange_rate_snapshot');

        if ($snapshot !== null) {
            return (float) $snapshot;
        }

        $snapshot = MerchandiseOrder::where('currency_id', $currencyId)
            ->whereDate('ordered_at', '<=', $asOfDate)
            ->whereNotNull('exchange_rate_snapshot')
            ->orderByDesc('ordered_at')
            ->value('exchange_rate_snapshot');

        if ($snapshot !== null) {
            return (float) $snapshot;
        }

        $snapshot = Refund::where('currency_id', $currencyId)
            ->whereDate('refunded_at', '<=', $asOfDate)
            ->whereNotNull('exchange_rate_snapshot')
            ->orderByDesc('refunded_at')
            ->value('exchange_rate_snapshot');

        if ($snapshot !== null) {
            return (float) $snapshot;
        }

        $currency = Currency::find($currencyId);
        return $currency?->exchange_rate ? (float) $currency->exchange_rate : null;
    }

    public function convertToBase(int $amount, int $targetCurrencyId, float $snapshotRate): int
    {
        if ($amount <= 0 || $snapshotRate <= 0.0) {
            return 0;
        }
        $amountInBaseUnits = $amount / $snapshotRate;
        return (int) round($amountInBaseUnits);
    }
}
