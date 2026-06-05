<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Classes;
use App\Services\Classes\ClassSessionGenerationService;
use InvalidArgumentException;

final class ClassesObserver
{
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
        $critical = ['start_date', 'end_date', 'recurrence_pattern_id'];
        $isChangingSchedule = collect($critical)->some(
            fn (string $field) => $class->isDirty($field)
        );

        if ($isChangingSchedule && $this->generator->hasBookings($class)) {
            throw new InvalidArgumentException(
                'This class has booked sessions. You cannot change the schedule because customers paid for these specific dates. Create a new class instead.'
            );
        }

        if ($class->isDirty('total_spots') && $this->generator->hasBookings($class)) {
            $newTotal = (int) $class->total_spots;
            if ($this->generator->wouldExceedCapacity($class, $newTotal)) {
                throw new InvalidArgumentException(
                    'Cannot reduce total spots below the number of currently reserved spots.'
                );
            }
        }
    }

    public function updated(Classes $class): void
    {
        $critical = ['start_date', 'end_date', 'recurrence_pattern_id'];
        $didChangeSchedule = collect($critical)->some(
            fn (string $field) => $class->wasChanged($field)
        );

        if (!$didChangeSchedule) {
            return;
        }

        $this->generator->regenerate($class);
    }

    public function deleting(Classes $class): void
    {
        if ($this->generator->hasBookings($class)) {
            throw new InvalidArgumentException(
                'Cannot delete this class: it has sessions with customer bookings. Cancel or migrate those bookings first.'
            );
        }

        $class->sessions()->forceDelete();
    }
}
