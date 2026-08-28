<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Commands\Admin\Operations\GetBackfillSessionsCommand;
use App\Enums\BookingSessionStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use App\Models\ClassSession;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Pagination\Cursor;

/**
 * Past, selectable class sessions inside a package's validity window.
 *
 * @see docs/historical-backfill/plan/phase-3-session-picker-endpoint.md
 */
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
            // Only sessions that have already happened — a historical record cannot cite a class
            // that has not run yet.
            ->whereDate('date', '<=', today())
            // NOT `status = completed`: nothing in the system ever sets that value, so filtering
            // on it returns zero rows and a silently empty picker. See finding A14.
            ->where('status', '!=', ClassSessionStatusEnum::CANCELLED->value)
            ->whereBetween('date', [
                $command->purchasedAt->toDateString(),
                $command->expiresAt->toDateString(),
            ])
            // Optional narrowing so an admin working through a long validity window can page one
            // month at a time (PRD §3.3).
            ->when($command->month, fn ($q, $month) => $q->whereMonth('date', $month))
            ->when($command->year, fn ($q, $year) => $q->whereYear('date', $year))
            // Sessions this client is already recorded in cannot be selected again — the write
            // path would reject them anyway (A07), so keep them out of the picker rather than
            // letting an admin build a selection that is guaranteed to fail.
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
            // id breaks ties: cursor pagination needs a deterministic total order, and
            // (date, start_time) is not unique across classes.
            ->orderBy('id')
            ->cursorPaginate($command->perPage, ['*'], 'cursor', $cursor);
    }
}
