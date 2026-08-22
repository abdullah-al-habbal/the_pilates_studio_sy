<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Classes;
use App\Services\Classes\ClassSessionGenerationService;
use Illuminate\Validation\ValidationException;

final class ClassesObserver
{
    private const SCHEDULE_FIELDS = [
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'recurrence_pattern_id',
    ];

    public function __construct(
        private readonly ClassSessionGenerationService $generator
    ) {}

    public function created(Classes $class): void
    {
        if ($class->recurrence_pattern_id) {
            $this->generator->generate($class);
        }
    }

    public function updating(Classes $class): void
    {
        if (collect(self::SCHEDULE_FIELDS)->contains(fn (string $field) => $class->isDirty($field))) {
            $this->generator->assertRegenerable($class);
        }

        if ($class->isDirty('total_spots')) {
            $this->generator->assertCapacityValid($class, (int) $class->total_spots);
        }
    }

    public function updated(Classes $class): void
    {
        $didChangeSchedule = collect(self::SCHEDULE_FIELDS)->contains(
            fn (string $field) => $class->wasChanged($field)
        );

        if (! $didChangeSchedule) {
            return;
        }

        $this->generator->regenerate($class);
    }

    public function deleting(Classes $class): void
    {
        if ($this->generator->hasBookings($class)) {
            throw ValidationException::withMessages([
                'class' => 'Cannot delete this class: it has sessions with customer bookings. Cancel or migrate those bookings first.',
            ]);
        }

        $class->sessions()->forceDelete();
    }
}
