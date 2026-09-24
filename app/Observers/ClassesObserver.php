<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ClassStatusEnum;
use App\Models\Classes;
use App\Models\ClassImage;
use App\Services\Classes\ClassSessionGenerationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class ClassesObserver
{
    private const SCHEDULE_FIELDS = [
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'recurrence_pattern_id',
        'weekdays',
    ];

    public function __construct(
        private readonly ClassSessionGenerationService $generator,
    ) {}

    public function created(Classes $class): void
    {
        $this->generator->generate($class);
    }

    public function updating(Classes $class): void
    {
        $isChangingSchedule = collect(self::SCHEDULE_FIELDS)->contains(
            fn (string $field) => $class->isDirty($field),
        );

        if (
            ! $isChangingSchedule
            && $class->isDirty('status')
            && $class->status === ClassStatusEnum::ACTIVE
        ) {
            $this->generator->assertActivatable($class);
        }

        if ($isChangingSchedule) {
            $this->generator->assertRegenerable($class);
        }

        if ($class->isDirty('total_spots')) {
            $this->generator->assertCapacityValid($class, (int) $class->total_spots);
        }
    }

    public function updated(Classes $class): void
    {
        $didChangeSchedule = collect(self::SCHEDULE_FIELDS)->contains(
            fn (string $field) => $class->wasChanged($field),
        );

        if ($didChangeSchedule) {
            $this->generator->regenerate($class);

            return;
        }

        if (
            $class->wasChanged('status')
            && $class->status === ClassStatusEnum::ACTIVE
            && ! $class->sessions()->exists()
        ) {
            $this->generator->generate($class);
        }
    }

    public function deleting(Classes $class): void
    {
        if ($this->generator->hasBookings($class)) {
            throw ValidationException::withMessages([
                'class' => 'Cannot delete this class: it has sessions with customer bookings. Cancel or migrate those bookings first.',
            ]);
        }

        if ($class->isForceDeleting()) {
            $imagePaths = $class->images()
                ->pluck('url')
                ->filter(fn (mixed $path): bool => is_string($path)
                    && $path !== ''
                    && ! str_starts_with($path, 'http://')
                    && ! str_starts_with($path, 'https://'))
                ->values()
                ->all();

            $class->pendingForceDeleteImagePaths = $imagePaths;
            $class->sessions()->withTrashed()->forceDelete();
            $class->images()->delete();

            return;
        }

        $class->sessions()->delete();
    }

    public function forceDeleted(Classes $class): void
    {
        $imagePaths = $class->pendingForceDeleteImagePaths;

        DB::afterCommit(static function () use ($imagePaths): void {
            $orphanedPaths = collect($imagePaths)
                ->reject(fn (string $path): bool => ClassImage::query()->where('url', $path)->exists())
                ->all();

            if ($orphanedPaths !== []) {
                Storage::disk('public')->delete($orphanedPaths);
            }
        });
    }

    public function restoring(Classes $class): void
    {
        $this->generator->assertRestorable($class);
    }

    public function restored(Classes $class): void
    {
        $this->generator->restoreOrGenerate($class);
    }
}
