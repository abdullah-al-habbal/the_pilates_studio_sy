<?php

declare(strict_types=1);

namespace App\Providers;

use App\Eloquent\Resolvers\PushNotificationLog\CreatePushNotificationLogResolver;
use App\Notifications\Fcm\FcmConfigValidator;
use App\Notifications\Fcm\FcmInvalidTokenDetector;
use App\Notifications\Fcm\FcmLogSaver;
use App\Notifications\Fcm\FcmMessageBuilder;
use App\Notifications\Fcm\FcmSender;
use App\Notifications\Fcm\FcmTokenDeleter;
use App\Notifications\Fcm\FcmTokenGetter;
use Illuminate\Support\ServiceProvider;

class FcmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FcmMessageBuilder::class);
        $this->app->singleton(FcmSender::class);
        $this->app->singleton(FcmLogSaver::class, function ($app) {
            return new FcmLogSaver($app->make(CreatePushNotificationLogResolver::class));
        });
        $this->app->singleton(FcmTokenGetter::class);
        $this->app->singleton(FcmConfigValidator::class);
        $this->app->singleton(FcmInvalidTokenDetector::class);
        $this->app->singleton(FcmTokenDeleter::class);
    }
}
