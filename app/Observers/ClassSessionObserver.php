<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ClassSession;
use InvalidArgumentException;

final class ClassSessionObserver
{
    public function deleting(ClassSession $session): void
    {
        if ($session->bookingSessions()->exists()) {
            throw new InvalidArgumentException(
                'Cannot delete this session: a customer has booked it. Cancel the booking first.'
            );
        }
    }
}
