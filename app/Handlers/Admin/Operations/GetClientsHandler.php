<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Commands\Admin\Operations\GetClientsCommand;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Cursor;

final readonly class GetClientsHandler
{
    public function handle(GetClientsCommand $command): LengthAwarePaginator|CursorPaginatorContract
    {
        if ($command->pagination === 'cursor') {
            return $this->cursorMode($command);
        }

        return $this->offsetMode($command);
    }

    private function cursorMode(GetClientsCommand $command): CursorPaginatorContract
    {
        $cursor = $command->cursor !== null ? Cursor::fromEncoded($command->cursor) : null;

        return User::query()
            ->customers()
            ->when($command->withValidFcm, fn ($q) => $q->whereHas('settings', function ($q) {
                $q->whereNotNull('fcm_token')->where('fcm_token', '!=', '');
            }))
            ->when($command->search, function ($q) use ($command) {
                $q->where(function ($subQ) use ($command) {
                    $subQ->where('fullname', 'like', "%{$command->search}%")
                        ->orWhere('phone_number', 'like', "%{$command->search}%");
                });
            })
            ->select(['id', 'fullname', 'phone_number'])
            ->orderBy('id')
            ->cursorPaginate(
                $command->perPage,
                ['id', 'fullname', 'phone_number'],
                'cursor',
                $cursor
            );
    }

    private function offsetMode(GetClientsCommand $command): LengthAwarePaginator
    {
        return User::with(['bookings.package', 'activeCreditBooking.package', 'frozenCreditBooking.package', 'bookingSessions'])
            ->customers()
            ->when($command->withValidFcm, fn ($q) => $q->whereHas('settings', function ($q) {
                $q->whereNotNull('fcm_token')->where('fcm_token', '!=', '');
            }))
            ->when($command->search, function ($q) use ($command) {
                $q->where(function ($subQ) use ($command) {
                    $subQ->where('fullname', 'like', "%{$command->search}%")
                        ->orWhere('phone_number', 'like', "%{$command->search}%");
                });
            })
            ->when($command->filter === 'most_attended' || $command->filter === 'best_user', function ($q) {
                $q->withCount(['bookingSessions as attended_count' => function ($query) {
                    $query->where('attendance_status', 'attended');
                }])->orderByDesc('attended_count');
            })
            ->when($command->filter === 'best_seller', function ($q) {
                $q->withCount('merchandiseOrders')
                    ->orderByDesc('merchandise_orders_count');
            })
            ->when($command->filter === 'most_active_booking', function ($q) {
                $q->withMax(['bookings as max_remaining_credits' => function ($query) {
                    $query->where('status', 'active');
                }], 'remaining_credits')
                    ->orderByDesc('max_remaining_credits');
            })
            ->when(! $command->filter, function ($q) {
                $q->latest();
            })
            ->paginate($command->perPage, ['*'], 'page', $command->page);
    }
}
