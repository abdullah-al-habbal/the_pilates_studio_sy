<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class GetClientsHandler
{

    public function handle(?string $search = null, int $page = 1, ?string $filter = null): LengthAwarePaginator
    {
        return User::with(['bookings.package', 'bookingSessions'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('fullname', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->when($filter === 'most_attended' || $filter === 'best_user', function ($q) {
                $q->withCount(['bookingSessions as attended_count' => function ($query) {
                    $query->where('attendance_status', 'attended');
                }])->orderByDesc('attended_count');
            })
            ->when($filter === 'best_seller', function ($q) {
                $q->withCount('merchandiseOrders')
                  ->orderByDesc('merchandise_orders_count');
            })
            ->when($filter === 'most_active_booking', function ($q) {
                $q->withMax(['bookings as max_remaining_credits' => function($query) {
                    $query->where('status', 'active');
                }], 'remaining_credits')
                ->orderByDesc('max_remaining_credits');
            })
            ->when(!$filter, function ($q) {
                $q->latest();
            })
            ->paginate(15, ['*'], 'page', $page);
    }
}
