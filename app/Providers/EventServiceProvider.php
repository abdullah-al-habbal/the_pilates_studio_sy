<?php

// filePath: app/Providers/EventServiceProvider.php

declare(strict_types=1);

namespace App\Providers;

use App\Events\User\UserRegisteredEvent;
use App\Events\UserSuccessfullyRegisteredEvent;
use App\Listeners\CreateInitialBookingForUserSuccessfullyRegisteredListener;
use App\Listeners\User\CreateDefaultUserSettingListener;
use App\Models\CenterMerchandiseCategory;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\MerchandiseOrder;
use App\Observers\CenterMerchandiseCategoryObserver;
use App\Observers\ClassesObserver;
use App\Observers\ClassSessionObserver;
use App\Observers\MerchandiseOrderObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserRegisteredEvent::class => [
            CreateDefaultUserSettingListener::class,
        ],
        UserSuccessfullyRegisteredEvent::class => [
            CreateInitialBookingForUserSuccessfullyRegisteredListener::class,
        ],
    ];

    protected $observers = [
        Classes::class => ClassesObserver::class,
        ClassSession::class => ClassSessionObserver::class,
        MerchandiseOrder::class => MerchandiseOrderObserver::class,
        CenterMerchandiseCategory::class => CenterMerchandiseCategoryObserver::class,
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
