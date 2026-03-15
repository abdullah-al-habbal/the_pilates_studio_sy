<?php

// filePath: app/Listeners/User/CreateDefaultUserSettingListener.php

declare(strict_types=1);

namespace App\Listeners\User;

use App\Events\User\UserRegisteredEvent;
use App\Models\Language;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class CreateDefaultUserSettingListener implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;

    public function handle(UserRegisteredEvent $event): void
    {
        $defaultLanguage = Language::getDefault()
            ?? throw new RuntimeException('Default language missing');

        $event->user->settings()->create([
            'preferred_language_id' => $defaultLanguage->id,
            'allow_notifications' => true,
        ]);
    }

    public function failed(UserRegisteredEvent $event, Throwable $exception): void
    {
        Log::error('CreateDefaultUserSettingListener failed permanently', [
            'user_id' => $event->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
