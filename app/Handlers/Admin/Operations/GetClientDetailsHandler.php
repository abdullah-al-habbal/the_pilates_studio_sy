<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\User;

final readonly class GetClientDetailsHandler
{
    /**
     * Fetch comprehensive client details with relations.
     */
    public function handle(int $userId): User
    {
        return User::with([
            'bookings.package',
            'merchandiseOrders.merchandise',
            'settings',
        ])->findOrFail($userId);
    }
}
