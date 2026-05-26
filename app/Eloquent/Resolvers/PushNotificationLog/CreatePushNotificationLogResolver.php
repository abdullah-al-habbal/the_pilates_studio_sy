<?php

declare(strict_types=1);

namespace App\Eloquent\Resolvers\PushNotificationLog;

use App\Models\PushNotificationLog;

final readonly class CreatePushNotificationLogResolver
{
    public function __construct(private PushNotificationLog $model) {}

    public function resolve(array $data): PushNotificationLog
    {
        return $this->model->create($data);
    }
}