<?php

declare(strict_types=1);

namespace App\Services\Classes;

use App\Enums\ClassSessionStatusEnum;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Services\Validation\ClassScheduleValidationService;
use App\Services\Validation\SessionConflictDetector;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ClassSessionManagementService
{
    public function __construct(
        private ClassScheduleValidationService $validator,
        private SessionConflictDetector $conflicts,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function create(int $classId, array $attributes): ClassSession
    {
        return DB::transaction(function () use ($classId, $attributes): ClassSession {
            $class = Classes::query()->lockForUpdate()->findOrFail($classId);
            $attributes = $this->validatedAttributes($class, $attributes);

            return ClassSession::query()->create(['class_id' => $class->id, ...$attributes]);
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(int $classId, int $sessionId, array $attributes): ClassSession
    {
        return DB::transaction(function () use ($classId, $sessionId, $attributes): ClassSession {
            $class = Classes::query()->lockForUpdate()->findOrFail($classId);
            $session = $class->sessions()->lockForUpdate()->findOrFail($sessionId);
            $attributes = $this->validatedAttributes($class, $attributes, $session->id);

            if ($session->bookingSessions()->exists() && collect($attributes)->some(
                fn (mixed $value, string $key): bool => $session->getAttribute($key) != $value,
            )) {
                throw ValidationException::withMessages(['session' => 'A booked session cannot be changed.']);
            }

            $session->update($attributes);

            return $session->refresh();
        });
    }

    public function delete(int $classId, int $sessionId): void
    {
        DB::transaction(function () use ($classId, $sessionId): void {
            $session = Classes::query()->lockForUpdate()->findOrFail($classId)
                ->sessions()->lockForUpdate()->findOrFail($sessionId);

            if ($session->bookingSessions()->exists()) {
                throw ValidationException::withMessages(['session' => 'A booked session cannot be deleted.']);
            }

            $session->delete();
        });
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    private function validatedAttributes(Classes $class, array $attributes, ?int $ignoreSessionId = null): array
    {
        $startTime = Carbon::parse($attributes['start_time'])->format('H:i:s');
        $endTime = Carbon::parse($attributes['end_time'])->format('H:i:s');
        $date = Carbon::parse($attributes['date'])->startOfDay();
        $this->validator->assertValidTimes($startTime, $endTime);
        $this->conflicts->assertNoConflicts(
            dates: [$date],
            startTime: $startTime,
            endTime: $endTime,
            instructorId: $class->instructor_id === null ? null : (int) $class->instructor_id,
            classId: (int) $class->id,
            ignoreSessionId: $ignoreSessionId,
            errorKey: 'date',
        );

        return [
            'date' => $date->toDateString(), 'start_time' => $startTime, 'end_time' => $endTime,
            'total_spots' => (int) $attributes['total_spots'], 'status' => ClassSessionStatusEnum::from($attributes['status'])->value,
        ];
    }
}
