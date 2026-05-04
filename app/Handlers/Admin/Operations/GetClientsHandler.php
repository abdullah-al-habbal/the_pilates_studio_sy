<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class GetClientsHandler
{
    /**
     * Search and paginate clients.
     */
    public function handle(?string $search = null, int $page = 1): LengthAwarePaginator
    {
        return User::with(['bookings.package', 'bookingSessions'])
            ->when($search, function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15, ['*'], 'page', $page);
    }
}
