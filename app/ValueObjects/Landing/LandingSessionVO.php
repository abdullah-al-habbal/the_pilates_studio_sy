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
        $className = $session->class?->getTranslation('title', app()->getLocale()) ?? 'N/A';
        $instructorName = $session->class?->instructor?->getTranslation('name', app()->getLocale()) ?? 'N/A';
        return new self(
            id: $session->id,
            time: $session->start_time ? Carbon::parse($session->start_time)->format('h:i A') : '',
            className: $className,
            instructorName: $instructorName,
            durationMinutes: (int) ($session->duration_minutes ?? 60),
            availableSpots: (int) ($session->available_spots ?? 0),
            isFull: $session->isFull(),
        );
    }
}
