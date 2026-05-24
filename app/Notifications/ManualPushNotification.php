<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ManualPushNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $title, public string $body) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function via($notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toFcm($notifiable): array
    {
        return [
            'title' => $this->title,
            'body'  => $this->body,
            'data'  => ['type' => 'manual'],
        ];
    }
}
