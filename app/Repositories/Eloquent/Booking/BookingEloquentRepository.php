<?php
// filePath: app/Repositories/Eloquent/Booking/BookingEloquentRepository.php
declare(strict_types=1);

namespace App\Repositories\Eloquent\Booking;

use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookingEloquentRepository
{
    public function find(int $id, bool $lockForUpdate = false, array $relations = []): ?Booking
    {
        $query = Booking::query();

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    public function findByUser(int $userId, int $id, array $relations = []): ?Booking
    {
        $query = Booking::query()->where('user_id', $userId);

        if (!empty($relations)) {
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
}
