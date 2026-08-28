<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Booking;
use App\Models\Package;
use App\Services\Currency\CurrencyService;
use App\Services\Currency\PricingService;

final readonly class BookingObserver
{
    public function __construct(
        private CurrencyService $currencyService,
        private PricingService $pricingService,
    ) {}

    public function creating(Booking $booking): void
    {
        if (! $booking->purchased_at) {
            $booking->purchased_at = $booking->created_at ?? now();
        }

        if ($booking->package_id && ! $booking->validity_days_snapshot) {
            $package = Package::find($booking->package_id);

            if ($package === null) {
                return;
            }

            $booking->validity_days_snapshot = $package->validity_days;

            if ($package->validity_days > 0 && ! $booking->expires_at) {
                $booking->expires_at = $booking->purchased_at
                    ->copy()
                    ->addDays($package->validity_days);
            }
        }
    }

    public function saving(Booking $booking): void
    {
        if ($booking->exchange_rate_snapshot !== null) {
            return;
        }

        $currencyId = $booking->currency_id
            ?? $this->currencyService->getBaseCurrency()->id;

        $booking->exchange_rate_snapshot = $this->pricingService
            ->getExchangeRateForSnapshot($currencyId);
    }
}
