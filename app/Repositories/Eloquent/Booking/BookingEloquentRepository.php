<?php
// filePath: app/Repositories/Eloquent/Booking/BookingEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Booking;

use App\Models\Booking;
use App\Services\Log\LoggingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BookingEloquentRepository
{
    public function __construct(
        private readonly LoggingService $logger
    ) {}

    public function listUserBookings(int $userId, array $filters = []): LengthAwarePaginator
    {
        return DB::transaction(function () use ($userId, $filters) {
            $this->logger->info('Fetching user bookings', ['user_id' => $userId]);

            return Booking::query()
                ->where('user_id', $userId)
                ->with(['package'])
                ->when($filters['status'] ?? null, fn($q, $status) =>
                    $q->where('status', $status))
                ->latest()
                ->paginate($filters['per_page'] ?? 20);
        });
    }

    public function findByUser(int $userId, int $id): ?Booking
    {
        try {
            return DB::transaction(function () use ($userId, $id) {
                $this->logger->info('Finding booking', ['user_id' => $userId, 'booking_id' => $id]);

                return Booking::query()
                    ->where('user_id', $userId)
                    ->with(['package'])
                    ->find($id);
            });
        } catch (\Exception $e) {
            $this->logger->error('Booking find failed', [
                'user_id' => $userId,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
