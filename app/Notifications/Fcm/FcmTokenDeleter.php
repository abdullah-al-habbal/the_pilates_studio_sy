<?php

declare(strict_types=1);

namespace App\Notifications\Fcm;

final class FcmTokenDeleter
{
    public function delete(mixed $notifiable, string $token): void
    {
        if (method_exists($notifiable, 'fcmTokens')) {
            $notifiable->fcmTokens()->where('token', $token)->delete();
            return;
        }

        if ($notifiable->fcm_token === $token) {
            $notifiable->forceFill(['fcm_token' => null])->save();
        }
    }
}
