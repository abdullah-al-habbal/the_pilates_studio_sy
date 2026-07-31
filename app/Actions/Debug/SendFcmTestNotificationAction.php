<?php

declare(strict_types=1);

namespace App\Actions\Debug;

use App\Models\User;
use App\Notifications\ManualPushNotification;
use Illuminate\Http\Response;

final class SendFcmTestNotificationAction
{
    public function __invoke(): Response
    {
        $user = User::with('settings')->first();

        if (! $user) {
            return response('No user found', 404);
        }

        $user->notify(new ManualPushNotification(
            title: 'Test',
            body: 'Direct HTTP check'
        ));

        return response('sent');
    }
}
