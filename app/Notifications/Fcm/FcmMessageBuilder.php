<?php

declare(strict_types=1);

namespace App\Notifications\Fcm;

use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

final class FcmMessageBuilder
{
    public function build(array $payload, string $token): CloudMessage
    {
        $title = $payload['title'] ?? 'Notification';
        $body  = $payload['body'] ?? '';
        $data  = array_map(static fn(mixed $v): string => (string) $v, $payload['data'] ?? []);

        $androidConfig = AndroidConfig::fromArray([
            'priority' => 'high',
            'notification' => ['sound' => 'default'],
        ]);

        $apnsConfig = ApnsConfig::fromArray([
            'payload' => [
                'aps' => [
                    'sound' => 'default',
                    'badge' => 1,
                    'content-available' => 1,
                ],
            ],
        ]);

        return CloudMessage::new()
            ->withToken($token)
            ->withNotification(FirebaseNotification::create($title, $body))
            ->withData($data)
            ->withAndroidConfig($androidConfig)
            ->withApnsConfig($apnsConfig)
            ->withHighestPossiblePriority();
    }
}
