<?php

declare(strict_types=1);

namespace App\Notifications\Fcm;

final class FcmTokenGetter
{
    public function getTokens(mixed $notifiable): array
    {
        if (! empty($notifiable->fcm_token)) {
            return [(string) $notifiable->fcm_token];
        }

        if (method_exists($notifiable, 'fcmTokens')) {
            return $notifiable->fcmTokens()
                ->pluck('token')
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return [];
    }
}
