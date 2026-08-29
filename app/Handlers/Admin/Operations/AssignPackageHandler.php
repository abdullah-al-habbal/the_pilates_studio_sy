<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Commands\Admin\Operations\AssignPackageCommand;
use App\Commands\Admin\Operations\HistoricalBackfillCommand;
use App\Commands\Booking\CreateBookingCommand;
use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use App\Services\Booking\BookingService;
use App\Services\Booking\HistoricalBackfillService;
use App\Services\Currency\PricingService;
use App\Services\Validation\AssignPackageValidatorService;
use App\Services\Validation\HistoricalBackfillValidatorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

final readonly class AssignPackageHandler
{
    private const LANG = 'dashboard.operations_ui.historical_backfill.';
    private const IDEMPOTENCY_TTL = 300;

    public function __construct(
        private BookingService $bookingService,
        private PricingService $pricingService,
        private AssignPackageValidatorService $validator,
        private HistoricalBackfillValidatorService $backfillValidator,
        private HistoricalBackfillService $backfillService,
    ) {}

    public function handle(AssignPackageCommand $command): Booking
    {
        $user = User::findOrFail($command->userId);

        $this->assertAccountAcceptsAPackage($user);

        if ($command->purchasedAt === null) {
            return $this->assignLive($user, $command);
        }

        return $this->recordHistorical($user, $command);
    }

    private function assertAccountAcceptsAPackage(User $user): void
    {
        if ($user->isFrozen()) {
            throw ValidationException::withMessages([
                'user_id' => __('dashboard.operations_ui.errors.frozen_account'),
            ]);
        }

        if ($user->bookings()->where('status', BookingStatusEnum::FROZEN)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => __('dashboard.operations_ui.errors.unfreeze_first'),
            ]);
        }
    }

    private function assignLive(User $user, AssignPackageCommand $command): Booking
    {
        $package = Package::findOrFail($command->packageId);
        $expiresAt = $package->validity_days ? now()->addDays($package->validity_days) : null;

        $resolvedCurrencyId = $command->currencyId ?? $this->pricingService->getBaseCurrencyId();

        $paidAmount = $this->validator->validateAndComputeAmount(
            $command->packageId,
            $resolvedCurrencyId,
            $command->clientSentAmount,
        );

        $exchangeRateSnapshot = $this->pricingService->getExchangeRateForSnapshot(
            $resolvedCurrencyId,
        );

        return $this->bookingService->createFromPackage(new CreateBookingCommand(
            user: $user,
            package: $package,
            expiresAt: $expiresAt,
            currencyId: $resolvedCurrencyId,
            paidAmount: $paidAmount,
            exchangeRateSnapshot: $exchangeRateSnapshot,
            createdBy: $command->createdBy,
        ));
    }

    private function recordHistorical(User $user, AssignPackageCommand $command): Booking
    {
        $idempotencyKey = (string) $command->idempotencyKey;

        $this->acquireIdempotencyKey($idempotencyKey);

        try {
            $plan = $this->backfillValidator->validate($user, new HistoricalBackfillCommand(
                userId: $command->userId,
                packageId: $command->packageId,
                purchasedAt: Carbon::parse($command->purchasedAt)->startOfDay(),
                currencyId: $command->currencyId,
                paidAmount: $command->clientSentAmount,
                attendedSessionIds: $command->attendedSessionIds,
                missedSessionIds: $command->missedSessionIds,
                exchangeRateOverride: $command->exchangeRateOverride,
            ));

            return $this->backfillService->backfill($plan, $command->createdBy);
        } catch (\Throwable $e) {
            Cache::forget($this->cacheKey($idempotencyKey));
            throw $e;
        }
    }

    private function acquireIdempotencyKey(string $key): void
    {
        if (Cache::add($this->cacheKey($key), true, self::IDEMPOTENCY_TTL)) {
            return;
        }

        throw ValidationException::withMessages([
            'idempotency_key' => __(self::LANG . 'error_duplicate_submission'),
        ]);
    }

    private function cacheKey(string $key): string
    {
        return "backfill:{$key}";
    }
}
