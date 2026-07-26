<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Scheduler;

use App\Commands\Admin\Scheduler\UpdateSessionCapacityCommand;
use App\Enums\BookingSessionStatusEnum;
use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final readonly class UpdateSessionCapacityHandler
{
    public function __construct(
        private ClassSessionEloquentRepository $repository,
    ) {}

    public function handle(UpdateSessionCapacityCommand $command): void
    {
        $session = $this->repository->findOrFailForUpdate($command->sessionId);

        $reserved = $session->bookingSessions()
            ->where('status', BookingSessionStatusEnum::RESERVED)
            ->count();

        if ($command->capacity < $reserved) {
            throw ValidationException::withMessages([
                'capacity' => "Capacity cannot be less than the {$reserved} already reserved booking(s).",
            ]);
        }

        $oldCapacity = (int) $session->total_spots;

        $session->update(['total_spots' => $command->capacity]);

        Log::info('[Capacity:Session]', [
            'session_id' => $command->sessionId,
            'class_id' => $session->class_id,
            'old_capacity' => $oldCapacity,
            'new_capacity' => $command->capacity,
            'reserved' => $reserved,
            'admin_id' => Auth::id(),
            'reason' => $command->reason,
        ]);
    }
}
