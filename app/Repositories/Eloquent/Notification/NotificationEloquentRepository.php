<?php

// filePath: app\Repositories\Eloquent\Notification\NotificationEloquentRepository.php
declare(strict_types=1);

namespace App\Repositories\Eloquent\Notification;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationEloquentRepository
{
    public function getUserNotifications(User $user, array $filters = []): LengthAwarePaginator
    {
        return $user->notifications()
            ->latest()
            ->when(isset($filters['unread']), fn (Builder $query) => $filters['unread'] ? $query->whereNull('read_at') : $query->whereNotNull('read_at')
            )
            ->paginate(20);
    }

    public function findUserNotification(User $user, int $id): ?AppNotification
    {
        return $user->notifications()->find($id);
    }

    public function markAsRead(User $user, int $id): bool
    {
        $notification = $this->findUserNotification($user, $id);

        if (! $notification || ! $notification->isUnread()) {
            return false;
        }

        return (bool) $notification->update(['read_at' => now()]);
    }

    public function bulkMarkAsRead(User $user, array $ids): int
    {
        return $user->notifications()
            ->whereIn('id', $ids)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
