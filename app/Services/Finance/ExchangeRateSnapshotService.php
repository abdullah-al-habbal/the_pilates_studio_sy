<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\BookingSourceTypeEnum;
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

        // Keyed on the business date, matching the MerchandiseOrder and Refund branches below.
        //
        // Backfilled bookings are excluded, and that exclusion is what makes this safe: a
        // historical backfill carries today's exchange rate by default while its purchased_at
        // sits months back, so including it would report today's rate as that period's rate.
        // With backfills out, every remaining booking has purchased_at == created_at by
        // construction, so the two columns agree here anyway.
        $snapshot = Booking::where('currency_id', $currencyId)
            ->where('source_type', '!=', BookingSourceTypeEnum::HISTORICAL_BACKFILL->value)
            ->whereDate('purchased_at', '<=', $asOfDate)
            ->whereNotNull('exchange_rate_snapshot')
            ->orderByDesc('purchased_at')
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
