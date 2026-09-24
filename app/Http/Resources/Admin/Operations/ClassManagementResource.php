<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Models\Classes;
use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Classes */
final class ClassManagementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Classes $class */
        $class = $this->resource;
        $locale = app()->getLocale();

        return [
            'id' => $class->id,
            'title' => $class->getTranslations('title'),
            'display_title' => $class->getTranslation('title', $locale, false) ?: $class->getTranslation('title', 'en'),
            'about' => $class->getTranslations('about'),
            'status' => $class->status->value,
            'trashed' => $class->trashed(),
            'instructor' => $class->instructor === null ? null : [
                'id' => $class->instructor->id,
                'name' => $class->instructor->getTranslations('name'),
                'display_name' => $class->instructor->getTranslation('name', $locale, false) ?: $class->instructor->getTranslation('name', 'en'),
            ],
            'category' => $class->category === null ? null : [
                'id' => $class->category->id,
                'name' => $class->category->getTranslations('name'),
                'display_name' => $class->category->getTranslation('name', $locale, false) ?: $class->category->getTranslation('name', 'en'),
            ],
            'schedule' => [
                'mode' => $class->hasWeekdaySchedule() ? 'weekdays' : 'interval',
                'weekdays' => $class->weekdays ?? [],
                'recurrence_pattern_id' => $class->recurrence_pattern_id,
                'recurrence' => $class->recurrencePattern === null ? null : [
                    'id' => $class->recurrencePattern->id,
                    'name' => $class->recurrencePattern->name,
                    'label' => $class->recurrencePattern->getTranslations('label'),
                    'unit' => $class->recurrencePattern->resolvedFrequencyUnit()->value,
                    'interval' => $class->recurrencePattern->resolvedFrequencyInterval(),
                ],
                'start_date' => $class->start_date?->toDateString(),
                'end_date' => $class->end_date?->toDateString(),
                'start_time' => $class->start_time,
                'end_time' => $class->end_time,
                'duration_minutes' => $class->duration_minutes,
            ],
            'total_spots' => $class->total_spots,
            'booking_sessions_count' => $class->booking_sessions_count ?? 0,
            'upcoming_sessions_count' => $class->upcoming_sessions_count ?? 0,
            'is_schedule_locked' => ($class->booking_sessions_count ?? 0) > 0,
            'images' => $this->whenLoaded('images', fn () => $class->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->url,
                'image_url' => $image->image_url,
                'is_primary' => $image->is_primary,
            ])->values()),
            'sessions' => $this->whenLoaded('sessions', fn () => $class->sessions->map(
                fn (ClassSession $session) => [
                    'id' => $session->id,
                    'date' => $session->date->toDateString(),
                    'start_time' => $session->start_time,
                    'end_time' => $session->end_time,
                    'status' => $session->status->value,
                    'total_spots' => $session->total_spots,
                    'available_spots' => $session->available_spots,
                    'booking_sessions_count' => $session->booking_sessions_count ?? 0,
                ],
            )->values()),
            'created_at' => $class->created_at?->toISOString(),
            'updated_at' => $class->updated_at?->toISOString(),
        ];
    }
}
