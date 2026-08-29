<?php

declare(strict_types=1);

namespace App\Commands\Booking;

use App\Models\Package;
use App\Models\User;
use Carbon\Carbon;

final readonly class CreateBookingCommand
{
    public function __construct(
        public User $user,
        public Package $package,
        public ?Carbon $expiresAt = null,
        public ?int $currencyId = null,
        public ?int $paidAmount = null,
        public ?float $exchangeRateSnapshot = null,
        public ?int $createdBy = null,
    ) {}
}
