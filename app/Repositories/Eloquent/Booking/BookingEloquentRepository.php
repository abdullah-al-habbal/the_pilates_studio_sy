<?php

// filePath: app/Repositories/Eloquent/Booking/BookingEloquentRepository.php
declare(strict_types=1);

namespace App\Repositories\Eloquent\Booking;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookingEloquentRepository
{
    public function getRevenueByCurrency(
        ?CarbonInterface $startDate = null,
        ?CarbonInterface $endDate = null,
        ?int $creatorId = null,
    ): Collection {
        return Booking::query()
            ->selectRaw('currency_id, SUM(paid_amount) as total, COUNT(*) as count')
            ->whereNotNull('paid_amount')
            ->when($creatorId, fn ($q) => $q->where('created_by', $creatorId))
            ->when($startDate, fn ($q) => $q->where('purchased_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('purchased_at', '<=', $endDate))
            ->groupBy('currency_id')
            ->get()
            ->map(fn ($item) => (object) [
                'currency_id' => (int) $item->currency_id,
                'total_revenue' => (int) $item->total,
                'booking_count' => (int) $item->count,
            ]);
    }

    public function getRevenueWithExchangeSnapshot(
        ?CarbonInterface $startDate = null,
        ?CarbonInterface $endDate = null,
        ?int $creatorId = null,
    ): Collection {
        return Booking::query()
            ->selectRaw('
                currency_id,
                SUM(paid_amount) as total,
                COUNT(*) as count,
                AVG(exchange_rate_snapshot) as avg_snapshot_rate
            ')
            ->whereNotNull('paid_amount')
            ->whereNotNull('exchange_rate_snapshot')
            ->when($creatorId, fn ($q) => $q->where('created_by', $creatorId))
            ->when($startDate, fn ($q) => $q->where('purchased_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('purchased_at', '<=', $endDate))
            ->groupBy('currency_id')
            ->get()
            ->map(fn ($item) => (object) [
                'currency_id' => (int) $item->currency_id,
                'total_revenue' => (int) $item->total,
                'booking_count' => (int) $item->count,
                'avg_exchange_rate_snapshot' => $item->avg_snapshot_rate ? (float) $item->avg_snapshot_rate : null,
            ]);
    }

    public function getTotalCount(?CarbonInterface $startDate = null, ?CarbonInterface $endDate = null, ?int $creatorId = null): int
    {
        return (int) Booking::query()
            ->when($creatorId, fn ($q) => $q->where('created_by', $creatorId))
            ->when($startDate, fn ($q) => $q->where('purchased_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('purchased_at', '<=', $endDate))
            ->count();
    }

    public function userHasActiveCreditBooking(int $userId): bool
    {
        return Booking::where('user_id', $userId)
            ->where('status', BookingStatusEnum::ACTIVE)
            ->where('remaining_credits', '>', 0)
            ->exists();
    }

    public function countActive(): int
    {
        return Booking::where('status', BookingStatusEnum::ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
    }

    public function sumTotalCredits(): int
    {
        return (int) Booking::sum('total_credits');
    }

    public function sumUsedCredits(): int
    {
        return (int) Booking::sum(DB::raw('total_credits - remaining_credits'));
    }

    public function getRevenueByPackage(): Collection
    {
        return Booking::with(['package' => fn ($q) => $q->withTrashed()])
            ->selectRaw('package_id, currency_id, COUNT(*) as bookings_count, SUM(paid_amount) as total_revenue')
            ->groupBy('package_id', 'currency_id')
            ->get()
            ->map(fn ($item) => (object) [
                'package_name' => $item->package?->getTranslation('name', app()->getLocale()) ?? 'Deleted Package',
                'currency_id' => (int) $item->currency_id,
                'revenue' => (int) ($item->total_revenue ?? 0),
                'bookings_count' => (int) $item->bookings_count,
            ]);
    }

    public function find(int $id, bool $lockForUpdate = false, array $relations = []): ?Booking
    {
        $query = Booking::query();

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    public function findByUser(int $userId, int $id, array $relations = []): ?Booking
    {
        $query = Booking::query()->where('user_id', $userId);

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    public function listUserBookings(int $userId, array $filters = []): LengthAwarePaginator
    {
        return Booking::query()
            ->where('user_id', $userId)
            ->with(['package'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate($filters['per_page'] ?? 20);
    }

    public function decrementCredits(int $id): void
    {
        Booking::query()
            ->where('id', $id)
            ->decrement('remaining_credits');
    }

    public function refundCredit(int $id): void
    {
        Booking::query()
            ->where('id', $id)
            ->increment('remaining_credits');
    }

    public function findActiveWithCreditsForUser(int $userId): ?Booking
    {
        return Booking::where('user_id', $userId)
            ->where('status', BookingStatusEnum::ACTIVE)
            ->where('remaining_credits', '>', 0)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->lockForUpdate()
            ->first();
    }

    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    /**
     * The booking, if any, currently occupying `active_user_id` for this user.
     *
     * The predicate is deliberately `status = active AND remaining_credits > 0` with **no expiry
     * clause**, because that is exactly the generated column behind the
     * `unique_active_booking_per_user` unique index. An expiry-filtered predicate would miss
     * stale rows — bookings past `expires_at` that still say
     * `active`, which are routine since nothing expires them on a schedule. Those rows still
     * occupy the index, so a guard built on the narrower predicate lets the caller through and
     * then dies on a raw 1062.
     *
     * The package is eager-loaded so one query serves both the conflict check and the placeholders
     * in the rejection message.
     *
     * @see docs/historical-backfill/decisions/D-A01-active-booking-conflict.md §C1
     */
    public function findBlockingActiveBooking(int $userId, bool $lock = false): ?Booking
    {
        return Booking::query()
            ->with('package')
            ->where('user_id', $userId)
            ->where('status', BookingStatusEnum::ACTIVE)
            ->where('remaining_credits', '>', 0)
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->first();
    }

    /**
     * Flip rows that are past their expiry but still marked active.
     *
     * Nothing expires bookings on a schedule, so these accumulate: useless to every feature that
     * reads them, yet still occupying `active_user_id` and blocking the next purchase. This
     * records a state change that already happened and was never written down.
     *
     * It only ever touches rows whose `expires_at` is in the past. A booking with credits and a
     * future expiry is left alone and still blocks — this is reconciliation, not supersession.
     */
    public function expireStaleActiveBookings(int $userId): int
    {
        return Booking::query()
            ->where('user_id', $userId)
            ->where('status', BookingStatusEnum::ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => BookingStatusEnum::EXPIRED->value]);
    }

    public function updateStatus(int $id, BookingStatusEnum $status): void
    {
        Booking::where('id', $id)->update(['status' => $status]);
    }

    public function expire(int $id): void
    {
        Booking::where('id', $id)->update([
            'status' => BookingStatusEnum::EXPIRED->value,
            'expires_at' => now(),
        ]);
    }

    public function cancel(int $id): void
    {
        $booking = Booking::findOrFail($id);
        $booking->update([
            'status' => BookingStatusEnum::CANCELLED->value,
            'remaining_credits' => $booking->total_credits,
        ]);
    }

    public function updateRemainingCredits(int $id, int $remaining): void
    {
        Booking::where('id', $id)->update(['remaining_credits' => $remaining]);
    }

    public function getTotalRevenueByCurrency(
        int $currencyId,
        ?CarbonInterface $startDate = null,
        ?CarbonInterface $endDate = null,
    ): int {
        return (int) Booking::query()
            ->where('currency_id', $currencyId)
            ->when($startDate, fn ($q) => $q->where('purchased_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('purchased_at', '<=', $endDate))
            ->sum('paid_amount');
    }
}
