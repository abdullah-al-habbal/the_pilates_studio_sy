<?php

declare(strict_types=1);

namespace App\Notifications\Fcm;

use Kreait\Firebase\Exception\MessagingException;

final class FcmInvalidTokenDetector
{
    public function isInvalidTokenException(MessagingException $e): bool
    {
        $msg = strtolower($e->getMessage());

        return str_contains($msg, 'not-registered')
            || str_contains($msg, 'invalid-registration')
            || str_contains($msg, 'unregistered');
    }
}
