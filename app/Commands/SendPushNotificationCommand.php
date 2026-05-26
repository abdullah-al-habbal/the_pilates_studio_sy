<?php

declare(strict_types=1);

namespace App\Commands;

final readonly class SendPushNotificationCommand
{
    public function __construct(
        public string $title,
        public string $body,
        public string $target,
        public array  $userIds = [],
    ) {}
}
