<?php

declare(strict_types=1);

namespace App\Notifications\Fcm;

use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;

final class FcmSender
{
    /**
     * @throws MessagingException
     */
    public function send(CloudMessage $message): string
    {
        $messaging = Firebase::messaging();
        $result = $messaging->send($message);
        return $result['name'] ?? '';
    }
}
