<?php

// filePath: app/Services/Notification/NotificationService.php
declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\AppNotification;
use App\Models\User;
use App\Repositories\Eloquent\Notification\NotificationEloquentRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private readonly NotificationEloquentRepository $NotificationEloquentRepository
    ) {}

    public function getUserNotifications(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->NotificationEloquentRepository->getUserNotifications($user, $filters);
    }

    public function findUserNotification(User $user, int $id): ?AppNotification
    {
        $notification = $this->NotificationEloquentRepository->findUserNotification($user, $id);

        if (! $notification) {
            Log::warning('Notification not found for user', [
                'user_id' => $user->id,
                'notification_id' => $id,
            ]);
        }

        return $notification;
    }

    public function markAsRead(User $user, int $id): bool
    {
        DB::beginTransaction();
        try {
            $success = $this->NotificationEloquentRepository->markAsRead($user, $id);
            DB::commit();

            return $success;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to mark notification as read', [
                'user_id' => $user->id,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function bulkMarkAsRead(User $user, array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        DB::beginTransaction();
        try {
            $updated = $this->NotificationEloquentRepository->bulkMarkAsRead($user, $ids);
            DB::commit();

            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk mark notifications as read', [
                'user_id' => $user->id,
                'notification_ids' => $ids,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
