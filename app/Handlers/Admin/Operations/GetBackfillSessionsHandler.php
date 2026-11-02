<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Commands\Admin\Operations\GetBackfillSessionsCommand;
use App\Enums\BookingSessionStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use App\Models\ClassSession;
use App\Support\Time\StudioTime;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Pagination\Cursor;

final readonly class GetBackfillSessionsHandler
{
    public function handle(GetBackfillSessionsCommand $command): CursorPaginatorContract
    {
        $cursor = $command->cursor !== null ? Cursor::fromEncoded($command->cursor) : null;

        return ClassSession::query()
            ->with(['class:id,title,instructor_id,class_category_id', 'class.instructor:id,name', 'class.category:id,name'])
            ->withCount([
                'bookingSessions as booking_sessions_count' => fn ($q) => $q
                    ->where('status', BookingSessionStatusEnum::RESERVED->value),
            ])
            ->whereDate('date', '<=', StudioTime::now()->toDateString())
            ->where('status', '!=', ClassSessionStatusEnum::CANCELLED->value)
            ->whereBetween('date', [
                $command->purchasedAt->toDateString(),
                ($command->expiresAt ?? StudioTime::now())->toDateString(),
            ])
            ->when($command->date, fn ($q, $date) => $q->whereDate('date', $date))
            ->when($command->instructorId, fn ($q, $id) => $q->whereHas('class', fn ($c) => $c->where('instructor_id', $id)))
            ->when($command->categoryId, fn ($q, $id) => $q->whereHas('class', fn ($c) => $c->where('class_category_id', $id)))
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
