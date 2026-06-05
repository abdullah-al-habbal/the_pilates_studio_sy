<?php
declare(strict_types=1);

namespace App\Http\Resources\Admin\Scheduler;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DailySessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        $session = $this->resource;

        $title = match (true) {
            is_array($session->class?->title) => $session->class->title[$locale]
                ?? $session->class->title['en']
                ?? '[MISSING:class.title.translation]',
            !is_null($session->class?->title) => $session->class->title,
            is_null($session->class) => '[MISSING:class_session.class_relation]',
            default => '[MISSING:class.title]',
        };

        $instructor = $session->class?->instructor?->name
            ?? ($session->class ? '[MISSING:instructor.name]' : '[MISSING:class_relation→instructor]');

        $reserved = $session->bookingSessions->count();
        $capacity = (int) ($session->total_spots ?? 0);
        $attended = $session->bookingSessions
            ->filter(fn($bs) => $bs->attendance_status?->value === 'attended')
            ->count();

        return [
            'id' => $session->id,
            'title' => $title,
            'instructor' => $instructor,
            'start_time' => substr($session->start_time ?? '00:00', 0, 5),
            'end_time' => substr($session->end_time ?? '00:00', 0, 5),
            'duration_minutes' => $session->duration_minutes,
            'capacity' => $capacity,
            'reserved' => $reserved,
            'attended' => $attended,
            'available_spots' => $capacity > 0 ? max(0, $capacity - $reserved) : null,
            'is_full' => $capacity > 0 && $reserved >= $capacity,
            'fill_pct' => $capacity > 0
                ? min(100, (int) round($reserved / $capacity * 100))
                : 0,
        ];
    }
}
