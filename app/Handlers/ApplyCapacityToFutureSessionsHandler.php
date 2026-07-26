<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Commands\ApplyCapacityToFutureSessionsCommand;
use App\Enums\BookingSessionStatusEnum;
use App\Models\Classes;
use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final readonly class ApplyCapacityToFutureSessionsHandler
{
    public function __construct(
        private ClassSessionEloquentRepository $repository,
    ) {}

    /**
     * @return array{affected: int, sessions: array}
     *
     * @throws ValidationException
     */
    public function handle(ApplyCapacityToFutureSessionsCommand $command): array
    {
        $class = Classes::findOrFail($command->classId);
        $newCapacity = (int) $class->total_spots;

        $futureSessions = $this->repository->getFutureScheduledSessions($command->classId);

        if ($futureSessions->isEmpty()) {
            throw ValidationException::withMessages([
                'capacity' => 'No future scheduled sessions found for this class.',
            ]);
        }

        $failures = [];

        foreach ($futureSessions as $session) {
            $reserved = $session->bookingSessions
                ->where('status', BookingSessionStatusEnum::RESERVED)
                ->count();

            if ($reserved > $newCapacity) {
                $failures[] = "Session on {$session->date->format('M j, Y')} at {$session->start_time} has {$reserved} reserved booking(s), which exceeds the new capacity of {$newCapacity}.";
            }
        }

        if ($failures !== []) {
            throw ValidationException::withMessages([
                'capacity' => implode(' ', $failures),
            ]);
        }

        $sessionIds = $futureSessions->pluck('id')->toArray();

        DB::transaction(function () use ($sessionIds, $newCapacity) {
            $this->repository->bulkUpdateCapacity($sessionIds, $newCapacity);
        });

        Log::info('[Capacity:Class]', [
            'class_id' => $command->classId,
            'new_capacity' => $newCapacity,
            'affected_sessions' => count($sessionIds),
            'admin_id' => Auth::id(),
            'reason' => $command->reason,
        ]);

        return [
            'affected' => count($sessionIds),
            'sessions' => $futureSessions->map(fn ($s) => [
                'id' => $s->id,
                'date' => $s->date->format('M j, Y'),
                'time' => substr($s->start_time, 0, 5),
            ])->toArray(),
        ];
    }
}
