<?php

// filePath: app/Repositories/Eloquent/BookingSession/BookingSessionEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\BookingSession;

use App\Enums\BookingSessionStatusEnum;
use App\Models\BookingSession;
use App\Services\Log\LoggingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BookingSessionEloquentRepository
{
    public function __construct(
        private readonly LoggingService $logger
    ) {
    }

    public function listUserSessions(int $userId, array $filters = []): LengthAwarePaginator
    {
        return DB::transaction(function () use ($userId, $filters) {
            $this->logger->info('Fetching user booking sessions', ['user_id' => $userId]);

            return BookingSession::query()
                ->whereHas('booking', fn($q) => $q->where('user_id', $userId))
                ->with(['classSession.class.instructor', 'classSession.class.primaryImage'])
                ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
                ->latest()
                ->paginate($filters['per_page'] ?? 20);
        });
    }

    public function findByUser(int $userId, int $id): ?BookingSession
    {
        try {
            return DB::transaction(function () use ($userId, $id) {
                $this->logger->info('Finding booking session', ['user_id' => $userId, 'session_id' => $id]);

                return BookingSession::query()
                    ->whereHas('booking', fn($q) => $q->where('user_id', $userId))
                    ->with(['classSession.class.instructor', 'classSession.class.primaryImage'])
                    ->find($id);
            });
        } catch (\Exception $e) {
            $this->logger->error('Booking session find failed', [
                'user_id' => $userId,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function create(array $data): BookingSession
    {
        return DB::transaction(function () use ($data) {
            $this->logger->info('Creating booking session', $data);

            return BookingSession::create($data);
        });
    }

    public function updateStatus(int $id, string $status): bool
    {
        return DB::transaction(function () use ($id, $status) {
            $this->logger->info('Updating booking session status', ['id' => $id, 'status' => $status]);

            return (bool) BookingSession::where('id', $id)->update(['status' => $status]);
        });
    }

    public function existsForBookingAndClassSession(int $bookingId, int $classSessionId): bool
    {
        return BookingSession::where('booking_id', $bookingId)
            ->where('class_session_id', $classSessionId)
            ->exists();
    }

    public function existsForUserAndClassSession(int $userId, int $classSessionId): bool
    {
        return BookingSession::query()
            ->whereIn('status', [
                BookingSessionStatusEnum::RESERVED->value,
                BookingSessionStatusEnum::ATTENDED->value,
            ])
            ->where('class_session_id', $classSessionId)
            ->whereHas('booking', fn($q) => $q->where('user_id', $userId))
            ->exists();
    }

    public function setCancelledAt(int $id): bool
    {
        return (bool) BookingSession::where('id', $id)->update(['cancelled_at' => now()]);
    }

    public function find(int $id, bool $lockForUpdate = false): ?BookingSession
    {
        $query = BookingSession::query()->where('id', $id);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }
}
