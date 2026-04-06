<?php

// app/Events/UserSuccessfullyRegisteredEvent.php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserSuccessfullyRegisteredEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user) {}
}
