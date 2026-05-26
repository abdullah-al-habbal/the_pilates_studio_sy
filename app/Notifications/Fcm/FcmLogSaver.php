<?php

declare(strict_types=1);

namespace App\Notifications\Fcm;

use App\Eloquent\Resolvers\PushNotificationLog\CreatePushNotificationLogResolver;

final class FcmLogSaver
{
    public function __construct(private readonly CreatePushNotificationLogResolver $resolver) {}

    public function saveSent(
        mixed $notifiable,
        string $notificationClass,
        array $payload,
        string $token,
        string $notificationId,
        ?string $messageId = null
    ): void {
        $this->resolver->resolve([
            'notifiable_type'    => $notifiable->getMorphClass(),
            'notifiable_id'      => $notifiable->getKey(),
            'notification_class' => $notificationClass,
            'data'               => [
                'payload'         => $payload,
                'token'           => $token,
                'notification_id' => $notificationId,
                'message_id'      => $messageId,
            ],
            'channel' => 'fcm',
            'sent_at' => now(),
        ]);
    }
}
