<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Scheduler;

use App\Commands\Admin\Scheduler\ProcessExistingWalkInCommand;
use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use App\Services\BookingSession\BookingSessionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ProcessExistingWalkInHandler
{
    public function __construct(
        private BookingSessionService $bookingSessionService,
        private ClassSessionEloquentRepository $classSessionRepo,
    ) {
    }

    public function handle(ProcessExistingWalkInCommand $command): array
    {
        $added = 0;
        $messages = [];
        $errors = [];

        DB::transaction(function () use ($command, &$added, &$messages, &$errors): void {
            $session = $this->classSessionRepo->findOrFailForUpdate($command->sessionId);
            $capacity = (int) ($session->total_spots ?? 0);
            $reserved = $this->classSessionRepo->countReserved($command->sessionId);
            $available = $capacity > 0 ? max(0, $capacity - $reserved) : PHP_INT_MAX;

            if ($capacity > 0 && count($command->userIds) > $available) {
                throw ValidationException::withMessages([
                    'user_ids' => "Only {$available} spot(s) remaining — cannot add " . count($command->userIds) . ' walk-in(s).',
                ]);
            }

            foreach ($command->userIds as $userId) {
                try {
                    $this->bookingSessionService->oneTimeAttend((int) $userId, $command->sessionId);
                    $added++;
                    $messages[] = "User #{$userId} added successfully.";
                } catch (ValidationException $e) {
                    $errors[] = array_values($e->errors())[0][0] ?? $e->getMessage();
                } catch (\Throwable $e) {
                    $errors[] = $e->getMessage();
                }
            }
        });

        return compact('added', 'messages', 'errors');
    }
}
