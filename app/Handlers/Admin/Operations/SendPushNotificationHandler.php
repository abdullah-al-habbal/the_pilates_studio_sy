<?php
declare(strict_types=1);
namespace App\Handlers\Admin\Operations;

use App\Models\User;
use App\Notifications\ManualPushNotification;
use Illuminate\Support\Facades\Log;

final readonly class SendPushNotificationHandler
{
    public function handle(
        string $title,
        string $body,
        string $target,
        array  $userIds = []
    ): array {
        $query = User::with('settings')
            ->whereHas('settings', fn($q) => $q->whereNotNull('fcm_token'));

        if ($target === 'specific') {
            $query->whereIn('id', $userIds);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            return [
                'dispatched'   => 0,
                'total_users'  => 0,
                'skipped'      => count($userIds),
                'reason'       => 'No users with registered FCM tokens found.',
            ];
        }

        $dispatched = 0;

        foreach ($users as $user) {
            try {
                $user->notify(new ManualPushNotification($title, $body));
                $dispatched++;
            } catch (\Throwable $e) {
                Log::error('SendPushNotificationHandler: dispatch failed', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return [
            'dispatched'  => $dispatched,
            'total_users' => $users->count(),
            'failed'      => $users->count() - $dispatched,
        ];
    }
}