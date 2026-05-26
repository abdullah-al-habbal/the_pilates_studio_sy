<?php

declare(strict_types=1);

namespace App\Notifications\Fcm;

final class FcmConfigValidator
{
    public function isConfigured(): bool
    {
        $default     = config('firebase.default');
        $credentials = config("firebase.projects.{$default}.credentials");
        return ! blank($credentials);
    }
}
