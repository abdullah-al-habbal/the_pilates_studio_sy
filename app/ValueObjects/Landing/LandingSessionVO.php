<?php

declare(strict_types=1);

namespace App\ValueObjects\Landing;

use App\Models\ClassSession;
use Carbon\Carbon;

class LandingSessionVO
{
    public function __construct(
        public readonly int $id,
        public readonly string $time,
        public readonly string $className,
        public readonly string $instructorName,
        public readonly int $durationMinutes,
        public readonly int $availableSpots,
        public readonly bool $isFull,
    ) {}

    public static function fromModel(ClassSession $session): self
    {
        $className = $session->class?->getTranslation('title', app()->getLocale()) ?? '';
        $instructorName = $session->class?->instructor?->getTranslation('name', app()->getLocale()) ?? '';
        return new self(
            id: $session->id,
            time: Carbon::parse($session->start_time)->format('h:i A'),
            className: $className,
            instructorName: $instructorName,
            durationMinutes: $session->duration_minutes,
            availableSpots: $session->available_spots,
            isFull: $session->is_full,
        );
    }
}
