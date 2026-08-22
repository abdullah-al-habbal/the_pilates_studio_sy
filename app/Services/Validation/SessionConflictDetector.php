<?php

declare(strict_types=1);

namespace App\Services\Validation;

use App\Enums\ClassSessionStatusEnum;
use App\Models\ClassSession;
use App\ValueObjects\Scheduling\ScheduleConflictVO;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Detects schedule collisions on real datetime windows.
 *
 * Scope is deliberately narrow, per the agreed business rules:
 *   - an instructor may not teach two overlapping sessions;
 *   - a class may not overlap itself.
 * Two classes with *different* instructors are free to overlap — the studio has
 * no room/resource model, so there is nothing else to contend over.
 *
 * Cancelled sessions release their slot. Soft-deleted sessions are ignored
 * entirely (note that the unique index on class_sessions does NOT ignore them,
 * so the two disagree by design — see assertNoDuplicates()).
 */
final readonly class SessionConflictDetector
{
    /**
     * @param  list<Carbon>  $dates
     * @return list<ScheduleConflictVO>
     */
    public function detect(
        array $dates,
        mixed $startTime,
        mixed $endTime,
        ?int $instructorId,
        ?int $classId,
        ?int $ignoreSessionId = null,
    ): array {
        if ($dates === []) {
            return [];
        }

        // With no instructor and no class to contend with, nothing can conflict.
        if ($instructorId === null && $classId === null) {
            return [];
        }

        $start = $this->normaliseTime($startTime);
        $end = $this->normaliseTime($endTime);

        $dateStrings = array_values(array_unique(
            array_map(fn (Carbon $date) => $date->toDateString(), $dates)
        ));

        $rows = ClassSession::query()
            ->join('classes', 'classes.id', '=', 'class_sessions.class_id')
            ->whereNull('class_sessions.deleted_at')
            ->whereNull('classes.deleted_at')
            ->where('class_sessions.status', '!=', ClassSessionStatusEnum::CANCELLED->value)
            ->whereIn('class_sessions.date', $dateStrings)
            // Half-open overlap: strict comparisons on both sides, so that
            // back-to-back sessions (16:00-17:00 then 17:00-18:00) do not collide.
            ->where('class_sessions.start_time', '<', $end)
            ->where('class_sessions.end_time', '>', $start)
            ->where(function (Builder $query) use ($instructorId, $classId) {
                if ($instructorId !== null) {
                    $query->orWhere('classes.instructor_id', $instructorId);
                }

                if ($classId !== null) {
                    $query->orWhere('class_sessions.class_id', $classId);
                }
            })
            ->when(
                $ignoreSessionId !== null,
                fn (Builder $query) => $query->where('class_sessions.id', '!=', $ignoreSessionId)
            )
            ->orderBy('class_sessions.date')
            ->orderBy('class_sessions.start_time')
            ->get([
                'class_sessions.id',
                'class_sessions.class_id',
                'class_sessions.date',
                'class_sessions.start_time',
                'class_sessions.end_time',
                'classes.instructor_id',
                'classes.title',
            ]);

        return $rows
            ->map(fn ($row) => new ScheduleConflictVO(
                date: Carbon::parse($row->date)->toDateString(),
                startTime: (string) $row->start_time,
                endTime: (string) $row->end_time,
                classId: (int) $row->class_id,
                classTitle: $this->titleOf($row),
                reason: ((int) $row->class_id === $classId)
                    ? ScheduleConflictVO::REASON_SAME_CLASS
                    : ScheduleConflictVO::REASON_INSTRUCTOR,
                sessionId: (int) $row->id,
            ))
            ->values()
            ->all();
    }

    /**
     * Reject the whole batch if anything at all conflicts.
     *
     * @param  list<Carbon>  $dates
     *
     * @throws ValidationException
     */
    public function assertNoConflicts(
        array $dates,
        mixed $startTime,
        mixed $endTime,
        ?int $instructorId,
        ?int $classId,
        ?int $ignoreSessionId = null,
        string $errorKey = 'start_date',
    ): void {
        $conflicts = $this->detect($dates, $startTime, $endTime, $instructorId, $classId, $ignoreSessionId);

        if ($conflicts === []) {
            return;
        }

        throw ValidationException::withMessages([
            $errorKey => __('dashboard.resources.classes.validation.conflicts_found', [
                'count' => count($conflicts),
                'details' => collect($conflicts)
                    ->take(5)
                    ->map(fn (ScheduleConflictVO $conflict) => $conflict->describe())
                    ->implode('; '),
            ]),
        ]);
    }

    /**
     * Guards the candidate batch against colliding with itself before insert.
     *
     * The unique index on (class_id, date, start_time) is the only other line of
     * defence and it surfaces as a raw QueryException, so catch it here where a
     * readable message is still possible. Soft-deleted rows count for this check
     * precisely because the index counts them.
     *
     * @param  list<Carbon>  $dates
     *
     * @throws ValidationException
     */
    public function assertNoDuplicates(array $dates, mixed $startTime, int $classId): void
    {
        $dateStrings = array_map(fn (Carbon $date) => $date->toDateString(), $dates);

        if (count($dateStrings) !== count(array_unique($dateStrings))) {
            throw ValidationException::withMessages([
                'weekdays' => __('dashboard.resources.classes.validation.duplicate_dates'),
            ]);
        }

        $clash = ClassSession::withTrashed()
            ->where('class_id', $classId)
            ->whereIn('date', $dateStrings)
            ->where('start_time', $this->normaliseTime($startTime))
            ->orderBy('date')
            ->value('date');

        if ($clash !== null) {
            throw ValidationException::withMessages([
                'start_date' => __('dashboard.resources.classes.validation.duplicate_session', [
                    'date' => Carbon::parse($clash)->toDateString(),
                ]),
            ]);
        }
    }

    /**
     * Times arrive as 'H:i' from Filament and 'H:i:s' from the database; the
     * comparison only works if both sides use the same shape.
     */
    private function normaliseTime(mixed $time): string
    {
        return Carbon::parse((string) $time)->format('H:i:s');
    }

    private function titleOf(object $row): string
    {
        $title = $row->title ?? null;

        if (is_string($title)) {
            $decoded = json_decode($title, true);

            if (is_array($decoded)) {
                return (string) ($decoded[app()->getLocale()] ?? reset($decoded) ?: '#'.$row->class_id);
            }

            return $title;
        }

        return '#'.$row->class_id;
    }
}
