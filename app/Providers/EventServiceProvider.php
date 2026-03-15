<?php

// filePath: app/Providers/EventServiceProvider.php

declare(strict_types=1);

namespace App\Providers;

use App\Events\User\UserRegisteredEvent;
use App\Listeners\User\CreateDefaultUserSettingListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserRegisteredEvent::class => [
            CreateDefaultUserSettingListener::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }

    public function shouldDiscoverEvents(): bool
    {
        return true;
    }
}
