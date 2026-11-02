<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Commands\Admin\Operations\GetBackfillSessionsCommand;
use App\Enums\BookingSessionStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use App\Models\ClassSession;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Pagination\Cursor;

final readonly class GetBackfillSessionsHandler
{
    public function handle(GetBackfillSessionsCommand $command): CursorPaginatorContract
    {
        $cursor = $command->cursor !== null ? Cursor::fromEncoded($command->cursor) : null;

        return ClassSession::query()
            ->with(['class:id,title,instructor_id', 'class.instructor:id,name'])
            ->withCount([
                'bookingSessions as booking_sessions_count' => fn ($q) => $q
                    ->where('status', BookingSessionStatusEnum::RESERVED->value),
            ])
            ->whereDate('date', '<=', today())
            ->where('status', '!=', ClassSessionStatusEnum::CANCELLED->value)
            ->whereBetween('date', [
                $command->purchasedAt->toDateString(),
                $command->expiresAt->toDateString(),
            ])
            ->when($command->month, fn ($q, $month) => $q->whereMonth('date', $month))
            ->when($command->year, fn ($q, $year) => $q->whereYear('date', $year))
            ->when(
                $command->excludeUserId,
                fn ($q, $userId) => $q->whereDoesntHave(
                    'bookingSessions',
                    fn ($bs) => $bs
                        ->where('status', BookingSessionStatusEnum::RESERVED->value)
                        ->whereHas('booking', fn ($b) => $b->where('user_id', $userId)),
                ),
            )
            ->orderBy('date')
            ->orderBy('start_time')
            ->orderBy('id')
            ->cursorPaginate($command->perPage, ['*'], 'cursor', $cursor);
    }
}
